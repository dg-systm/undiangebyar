<?php

namespace App\Http\Controllers;

use App\Models\Draw;
use App\Models\Kendaraan;
use App\Models\Prize;
use App\Models\Winner;
use App\Services\DrawingService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DrawController extends Controller
{
    public function __construct(
        protected DrawingService $drawingService
    ) {}

    /**
     * Proses pengundian.
     *
     * - Undian biasa : per koordinator/wilayah (Samsat), 5 pemenang per Samsat
     *                  (jumlah dapat diatur dari sidebar).
     * - Grand Prize  : global semua kecamatan, jumlah pemenang fleksibel.
     */
    public function pick(Request $request)
    {
        $isGlobal = $request->boolean('is_global', false);
        $isDemo   = $request->boolean('is_demo', false);

        if ($isGlobal) {
            $request->validate([
                'prize_ids'   => 'required|array|min:1',
                'prize_ids.*' => 'required|numeric|exists:prizes,id',
                'quantity'    => 'required|integer|min:1',
            ]);
        } else {
            $request->validate([
                'prize_ids'   => 'required|array|min:1',
                'prize_ids.*' => 'required|numeric|exists:prizes,id',
                'samsats'     => 'required|array|min:1',
                'samsats.*'   => 'required|string',
                'per_samsat'  => 'nullable|integer|min:1',
            ]);
        }

        $prizeIds = $request->prize_ids;

        // 1. Validasi prize dan ketersediaan
        $prizes      = Prize::whereIn('id', $prizeIds)->get();
        $categoryIds = $prizes->pluck('prize_category_id')->unique();

        if ($categoryIds->count() > 1) {
            return response()->json([
                'message' => 'Semua hadiah harus berasal dari satu kategori yang sama.'
            ], 422);
        }

        $drawCategoryId = $categoryIds->first();
        $isBbnkb        = $prizes->first()?->prize_category?->isBbnkb() ?? false;
        $totalAvailable = $prizes->sum(fn($p) => $p->available_quantity);

        $perSamsat = (int) $request->input('per_samsat', 5);

        // Kunci proses pengundian: front-end yang timeout bisa mencoba lagi
        // padahal request pertama masih diproses server. Tanpa kunci, dua
        // proses bersamaan bisa memilih kendaraan yang sama (pemenang ganda).
        // Request demo tidak menulis database, jadi tidak perlu dikunci.
        $runPick = fn () => $isGlobal
            ? $this->pickGlobal(
                $request,
                $prizeIds,
                $prizes,
                $drawCategoryId,
                $totalAvailable,
                $isDemo,
                $isBbnkb
            )
            : $this->pickBySamsat(
                $prizeIds,
                $request->samsats,
                $totalAvailable,
                $isDemo,
                $perSamsat,
                $isBbnkb
            );

        return $this->withDrawLock($isDemo, $runPick);
    }

    /**
     * Jalankan proses pengundian di bawah kunci aplikasi agar request
     * pengundian yang bersamaan (mis. retry front-end) diproses berurutan.
     *
     * Kunci memakai cache driver sehingga berlaku untuk semua driver database.
     */
    protected function withDrawLock(bool $isDemo, \Closure $callback)
    {
        if ($isDemo) {
            return $callback();
        }

        $lock = Cache::lock('draw-pick', 120);

        try {
            $lock->block(30);
        } catch (LockTimeoutException $e) {
            return response()->json([
                'message' => 'Masih ada proses pengundian lain yang berjalan. Tunggu beberapa detik lalu coba lagi.',
            ], 422);
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    /**
     * Grand Prize: undian global dari semua kecamatan.
     *
     * Jumlah pemenang bebas sesuai kebutuhan acara (fleksibel), tanpa
     * komposisi roda. Kandidat tetap diambil melalui SP getWinner mode
     * global (id_lokasi = 999) yang sudah ada.
     */
    protected function pickGlobal(
        Request $request,
        array $prizeIds,
        $prizes,
        $drawCategoryId,
        $totalAvailable,
        bool $isDemo,
        bool $isBbnkb = false
    ) {
        $required = (int) $request->quantity;

        if ($totalAvailable < $required) {
            return response()->json([
                'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$required})."
            ], 422);
        }

        $spWinners = $this->drawingService->getWinnerCandidates(999, '', $required, 10, $isBbnkb);

        if ($spWinners->count() < $required) {
            return response()->json([
                'message' => "Tidak cukup kendaraan tersedia. Tersedia: {$spWinners->count()}, dibutuhkan: {$required}."
            ], 422);
        }

        $selectedIds = $spWinners
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($selectedIds->count() < $required) {
            return response()->json([
                'message' => "Gagal memilih kendaraan yang cukup. Coba lagi."
            ], 422);
        }

        $vehicles = Kendaraan::whereIn('id', $selectedIds)->get()->keyBy('id');

        if ($vehicles->count() < $required) {
            return response()->json([
                'message' => "Gagal memilih kendaraan yang cukup. Coba lagi."
            ], 422);
        }

        $prizePool = $this->drawingService->buildPrizePool($prizes, $required);

        // Penyimpanan draw + pemenang dibungkus transaksi: bila salah satu
        // gagal (mis. koneksi database putus di tengah proses), tidak ada
        // pemenang parsial yang tersimpan dan stok hadiah tetap konsisten.
        [$draw, $results] = DB::transaction(function () use (
            $isDemo,
            $prizeIds,
            $required,
            $drawCategoryId,
            $selectedIds,
            $vehicles,
            $prizes,
            $prizePool
        ) {
            $draw = null;
            if (!$isDemo) {
                $draw = Draw::create([
                    'prize_id'          => $prizeIds[0],
                    'prize_category_id' => $drawCategoryId,
                    'quantity'          => $required,
                    // Penanda grand prize (global).
                    'is_global'         => true,
                ]);
            }

            $results = [];
            foreach ($selectedIds as $index => $id) {
                $prizeId = $prizePool[$index];
                $vehicle = $vehicles[$id];

                if (!$isDemo) {
                    Winner::create([
                        'draw_id'      => $draw->id,
                        'kendaraan_id' => $id,
                        'id_kendaraan' => $vehicle->id_kendaraan,
                        'prize_id'     => $prizeId,
                    ]);
                }

                $vehicle->prize = $prizes->firstWhere('id', $prizeId);
                $results[]      = $vehicle;
            }

            return [$draw, $results];
        });

        $response = ['data' => $results];
        if ($draw) {
            $response['draw_id'] = $draw->id;
        }

        return response()->json($response);
    }

    protected function pickBySamsat(
        array $prizeIds,
        array $samsats,
        $totalAvailable,
        bool $isDemo,
        int $perSamsat = 5,
        bool $isBbnkb = false
    ) {
        $required = count($samsats) * $perSamsat;

        if ($totalAvailable < $required) {
            return response()->json([
                'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$required}). Pilih lebih banyak hadiah atau kurangi jumlah Samsat."
            ], 422);
        }

        $response = DB::transaction(function () use ($prizeIds, $samsats, $perSamsat, $isDemo, $isBbnkb) {
            return $this->drawingService->pickDraw($prizeIds, $samsats, $perSamsat, $isDemo, $isBbnkb);
        });

        if (isset($response['status']) && $response['status'] === 422) {
            return response()->json([
                'message' => $response['message'],
            ], 422);
        }

        return response()->json($response);
    }

    /**
     * Hasil undian (non-demo) yang baru saja tersimpan.
     *
     * Dipakai untuk memulihkan tampilan di front-end bila koneksi terputus
     * (mis. perangkat standby lama): request pengundian mungkin sudah
     * selesai dan tersimpan di server, tetapi respons tidak sempat
     * diterima front-end. Pemenang dikembalikan dalam bentuk yang sama
     * dengan respons endpoint pick.
     *
     * Front-end mengirim konteks pengundian yang sedang berjalan
     * (is_global, quantity, prize_id, samsats) agar draw yang dipulihkan
     * benar-benar draw itu sendiri — bukan undian lama yang kebetulan
     * baru saja tersimpan (staleness recovery).
     */
    public function recent(Request $request)
    {
        $isGlobal = $request->boolean('is_global', false);
        $quantity = $request->query('quantity');
        $prizeId  = $request->query('prize_id');
        $samsats  = (array) $request->query('samsats', []);

        $query = Draw::query()
            ->where('created_at', '>=', now()->subMinutes(3))
            ->where('is_global', $isGlobal)
            ->latest('id');

        // Cocokkan dengan konteks undian yang sedang berjalan di front-end.
        if ($quantity !== null && $quantity !== '') {
            $query->where('quantity', (int) $quantity);
        }

        if ($prizeId !== null && $prizeId !== '') {
            $query->where('prize_id', (int) $prizeId);
        }

        $draw = $query->first();

        if (!$draw) {
            return response()->json([
                'data'      => [],
                'recovered' => false,
            ]);
        }

        $winners = $draw->winners()->with(['kendaraan', 'prize.prize_category'])->get();

        $data = $winners
            ->map(function (Winner $winner) {
                $vehicle = $winner->kendaraan;

                if (!$vehicle) {
                    return null;
                }

                $vehicle->prize = $winner->prize;

                // Pertahankan lokasi Samsat/wilayah untuk pengelompokan
                // undian biasa; bila kosong, pakai id kecamatan.
                $vehicle->lokasi = $vehicle->lokasi ?: ($vehicle->id_kecamatan ?? '');

                return $vehicle;
            })
            ->filter()
            ->values();

        // Undian biasa ditampilkan front-end dalam kelompok per Samsat
        // sesuai urutan form.samsats. Urutkan hasil agar kelompoknya
        // konsisten (urutan kendaraan_id saja membuat hasil tampil
        // tercampur antar Samsat setelah pemulihan).
        if (!$isGlobal && count($samsats) > 0 && $data->isNotEmpty()) {
            $data = $this->orderWinnersBySamsats($data, $samsats);
        }

        return response()->json([
            'data'      => $data,
            'draw_id'   => $draw->id,
            'recovered' => $data->isNotEmpty(),
        ]);
    }

    /**
     * Samakan penulisan nama Samsat agar pencocokan tidak sensitif huruf:
     * "Kab. Grobogan" == "GROBOGAN", label gabungan memakai pemisah "->".
     */
    protected function normalizeSamsatLabel(?string $value): string
    {
        $value = str_replace(['â†’', '→'], '->', (string) $value);
        $value = str_replace(['Kab.', 'Kota'], '', $value);

        return strtoupper(preg_replace('/\s+/', ' ', trim($value)));
    }

    /**
     * Daftar nama Samsat dari satu label kelompok. Label gabungan seperti
     * "Blora -> Cepu" atau "Klaten -> Prambanan, Delanggu" berisi lebih
     * dari satu Samsat, sama seperti DrawingService::parseSamsatNames.
     *
     * @return array<int, string>
     */
    protected function samsatLabelGroup(string $samsat): array
    {
        $parts = explode('->', $this->normalizeSamsatLabel($samsat), 2);

        $names = [trim($parts[0])];

        if (isset($parts[1])) {
            foreach (explode(',', $parts[1]) as $subName) {
                $names[] = trim($subName);
            }
        }

        return array_values(array_filter($names));
    }

    /**
     * Urutkan kendaraan pemenang mengikuti urutan kelompok Samsat pada
     * form front-end (samsats). Baris yang tidak cocok tetap ditampilkan
     * di akhir agar tidak hilang.
     */
    protected function orderWinnersBySamsats(Collection $data, array $samsats): Collection
    {
        $groups = [];
        foreach (array_values($samsats) as $index => $samsat) {
            $groups[$index] = $this->samsatLabelGroup((string) $samsat);
        }

        $grouped  = [];
        $leftover = [];

        foreach ($data as $row) {
            $lokasi = $this->normalizeSamsatLabel($row->lokasi ?? '');
            $groupIndex = null;

            foreach ($groups as $index => $names) {
                if (in_array($lokasi, $names, true)) {
                    $groupIndex = $index;
                    break;
                }
            }

            if ($groupIndex === null) {
                $leftover[] = $row;
            } else {
                $grouped[$groupIndex][] = $row;
            }
        }

        ksort($grouped);

        $ordered = [];
        foreach ($grouped as $rows) {
            foreach ($rows as $row) {
                $ordered[] = $row;
            }
        }

        return collect(array_merge($ordered, $leftover));
    }
}
