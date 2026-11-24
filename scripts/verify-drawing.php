<?php

use App\Models\Winner;
use App\Services\DrawingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/** @var DrawingService $service */
$service = $app->make(DrawingService::class);
$errors = [];
$locations = collect(config('uppd', []))
    ->except([999])
    ->map(fn ($name) => trim((string) $name))
    ->values();

echo 'Jumlah Samsat: '.$locations->count().PHP_EOL;

foreach ($locations as $name) {
    $id = $service->getSamsatIds($name)[0] ?? null;

    if ($id === null) {
        $errors[] = "{$name}: id_lokasi tidak ditemukan";

        continue;
    }

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $result = $service->pickDraw([1], [$name], 5, true);

        if (isset($result['status'])) {
            $errors[] = "{$name} (percobaan {$attempt}): ".$result['message'];

            continue;
        }

        $data = collect($result['data'] ?? []);
        $unique = $data->pluck('id')->unique()->count();

        if ($data->count() !== 5 || $unique !== 5) {
            $errors[] = "{$name} (percobaan {$attempt}): hasil ".$data->count()." baris, unik {$unique}";
        }
    }

    $bbn = $service->getWinner($id, '', 5, true);

    if (count($bbn) !== 5) {
        $errors[] = "{$name}: getWinnerBbn mengembalikan ".count($bbn).' baris';
    }
}

for ($attempt = 1; $attempt <= 3; $attempt++) {
    $global = $service->getWinner(999, '', 10, false);
    $unique = collect($global)->pluck('id')->unique()->count();

    if (count($global) !== 10 || $unique !== 10) {
        $errors[] = "Grand Prize (percobaan {$attempt}): hasil ".count($global)." baris, unik {$unique}";
    }
}

$allNames = $locations->all();
$combined = $service->pickDraw([1], $allNames, 3, true);

if (isset($combined['status'])) {
    $errors[] = 'Gabungan semua Samsat: '.$combined['message'];
} else {
    $data = collect($combined['data'] ?? []);
    $unique = $data->pluck('id')->unique()->count();
    $expected = count($allNames) * 3;

    if ($data->count() !== $expected || $unique !== $expected) {
        $errors[] = 'Gabungan semua Samsat: hasil '.$data->count()." baris, unik {$unique}, seharusnya {$expected}";
    }

    $byLocation = $data->groupBy('lokasi')->map->count();

    foreach ($allNames as $name) {
        if (($byLocation[$name] ?? 0) !== 3) {
            $errors[] = "Gabungan semua Samsat: {$name} mendapat ".($byLocation[$name] ?? 0).' baris';
        }
    }
}

// Uji persis grup Samsat yang ditampilkan front-end, termasuk Samsat
// Pembantu gabungan seperti "Blora → Cepu" dan
// "Klaten → Prambanan, Delanggu".
foreach (App\Support\Wilayah::coordinatorOptions() as $plat => $option) {
    $groups = $option['samsat'] ?? [];
    $result = $service->pickDraw([1], $groups, 5, true);

    if (isset($result['status'])) {
        $errors[] = "Koordinator {$plat}: ".$result['message'];

        continue;
    }

    $data = collect($result['data'] ?? []);
    $unique = $data->pluck('id')->unique()->count();
    $expected = count($groups) * 5;

    if ($data->count() !== $expected || $unique !== $expected) {
        $errors[] = "Koordinator {$plat}: hasil ".$data->count()." baris, unik {$unique}, seharusnya {$expected}";
    }
}

// Uji penyimpanan pemenang (non-demo) lalu rollback agar database tetap bersih.
DB::beginTransaction();

try {
    $saved = $service->pickDraw([1], ['Cilacap'], 5, false);

    if (isset($saved['status'])) {
        $errors[] = 'Simpan pemenang: '.$saved['message'];
    } else {
        $drawId = $saved['draw_id'] ?? null;
        $winnerCount = $drawId ? Winner::where('draw_id', $drawId)->count() : -1;

        if ($winnerCount !== 5) {
            $errors[] = "Simpan pemenang: {$winnerCount} baris winners untuk draw {$drawId}";
        }

        // Undian kedua pada Samsat yang sama tidak boleh mengulang pemenang.
        $savedAgain = $service->pickDraw([1], ['Cilacap'], 5, false);

        if (isset($savedAgain['status'])) {
            $errors[] = 'Undian kedua: '.$savedAgain['message'];
        } else {
            $firstIds = collect($saved['data'] ?? [])->pluck('id')->all();
            $secondIds = collect($savedAgain['data'] ?? [])->pluck('id')->all();
            $overlap = array_intersect($firstIds, $secondIds);
            $unique = collect(array_merge($firstIds, $secondIds))->unique()->count();

            if ($overlap !== [] || $unique !== 10) {
                $errors[] = 'Undian kedua mengulang pemenang: '.count($overlap).' duplikat, unik '.$unique;
            }
        }
    }
} finally {
    DB::rollBack();
}

// Uji jalur HTTP/controller seperti yang dipakai front-end.
try {
    $httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

    $getRoutes = [
        '/' => null,
        '/api/get-data/prize' => null,
        '/api/get-data/coordinator' => null,
        '/api/get-data/wilayah' => null,
    ];

    foreach ($getRoutes as $uri => $unused) {
        $request = Request::create($uri, 'GET');
        $request->headers->set('Accept', 'application/json');
        $response = $httpKernel->handle($request);

        if ($response->getStatusCode() !== 200) {
            $errors[] = "GET {$uri}: status ".$response->getStatusCode();
        }

        $payload = json_decode($response->getContent(), true);

        if ($uri === '/' && ! str_contains($response->getContent(), 'window.appConfig')) {
            $errors[] = 'Dashboard tidak memuat window.appConfig';
        }

        if ($uri === '/api/get-data/prize' && ! is_array($payload)) {
            $errors[] = 'API prize bukan JSON array';
        }

        if ($uri === '/api/get-data/coordinator' && count($payload ?? []) < 1) {
            $errors[] = 'API coordinator kosong';
        }

        if ($uri === '/api/get-data/wilayah' && ($payload['scope'] ?? null) !== 'province') {
            $errors[] = 'API wilayah bukan cakupan provinsi';
        }

        $httpKernel->terminate($request, $response);
    }

    $request = Request::create('/api/draw/pick', 'POST', [
        'prize_ids' => [1],
        'samsats' => ['Bandongan'],
        'per_samsat' => 5,
        'is_demo' => true,
    ]);
    $request->headers->set('Accept', 'application/json');

    $response = $httpKernel->handle($request);
    $payload = json_decode($response->getContent(), true);

    if (($response->getStatusCode() !== 200) || count($payload['data'] ?? []) !== 5) {
        $errors[] = 'Controller pick: status '.$response->getStatusCode().', data '.count($payload['data'] ?? []);
    }

    $httpKernel->terminate($request, $response);
} catch (Throwable $e) {
    $errors[] = 'Controller pick: '.$e->getMessage();
}

echo PHP_EOL;

if ($errors === []) {
    echo 'SEMUA PENGUJIAN LULUS'.PHP_EOL;
    exit(0);
}

echo 'DITEMUKAN '.count($errors).' MASALAH:'.PHP_EOL;

foreach ($errors as $error) {
    echo ' - '.$error.PHP_EOL;
}

exit(1);
