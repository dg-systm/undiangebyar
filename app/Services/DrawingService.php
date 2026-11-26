<?php

namespace App\Services;

use App\Models\Draw;
use App\Models\Kendaraan;
use App\Models\Prize;
use App\Models\Winner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DrawingService
{
    public const PROCEDURE_DEFAULT = 'getWinner';

    public const PROCEDURE_BBNKB = 'getWinnerBbn';

    protected ?array $samsatIdMap = null;

    public function getWinnerMySql(int $idLokasi, ?string $idKecamatan, int $totalWinner = 5)
    {
        return DB::select('CALL getWinner(?, ?, ?)', [
            $idLokasi,
            $idKecamatan ?? '',
            $totalWinner,
        ]);
    }

    public function getWinnerSqlsrv(int $idLokasi, ?string $idKecamatan, int $totalWinner = 5, string $procedure = self::PROCEDURE_DEFAULT)
    {
        $procedure = $this->resolveProcedureName($procedure, 'sqlsrv');

        return DB::select("EXEC {$procedure} ?, ?, ?", [
            $idLokasi,
            $idKecamatan ?? '',
            $totalWinner,
        ]);
    }

    /**
     * Panggil SP getWinner sesuai signature database:
     * getWinner(@id_lokasi_samsat, @id_kecamatan, @length).
     *
     * Untuk kategori hadiah BBNKB, SP getWinnerBbn dipakai agar hanya
     * kendaraan dengan id_pendaftaran = 1 yang ikut diundi.
     *
     * @param  string|null  $idKecamatan  Kode kecamatan (mis. "0801"); '' = semua kecamatan.
     * @param  bool  $isBbnkb  true = gunakan SP getWinnerBbn.
     */
    public function getWinner(int $idLokasi, ?string $idKecamatan = '', int $totalWinner = 5, bool $isBbnkb = false)
    {
        $driver = DB::connection()->getDriverName();
        $procedure = $isBbnkb ? self::PROCEDURE_BBNKB : self::PROCEDURE_DEFAULT;

        return match ($driver) {
            'mysql', 'mariadb' => $this->getWinnerMySql($idLokasi, $idKecamatan, $totalWinner),
            'sqlsrv'           => $this->getWinnerSqlsrv($idLokasi, $idKecamatan, $totalWinner, $procedure),
            default => $this->getWinnerMySql($idLokasi, $idKecamatan, $totalWinner),
        };
    }

    /**
     * Pastikan nama SP hanya berasal dari daftar yang diizinkan.
     *
     * getWinnerBbn hanya tersedia di SQL Server; driver lain memakai getWinner.
     */
    protected function resolveProcedureName(string $procedure, string $driver): string
    {
        $allowed = [self::PROCEDURE_DEFAULT, self::PROCEDURE_BBNKB];

        if (!in_array($procedure, $allowed, true)) {
            return self::PROCEDURE_DEFAULT;
        }

        if ($procedure === self::PROCEDURE_BBNKB && $driver !== 'sqlsrv') {
            return self::PROCEDURE_DEFAULT;
        }

        return $procedure;
    }

    /**
     * Ambil kandidat pemenang dari SP sampai jumlah yang diminta terpenuhi.
     *
     * SP memfilter kendaraan yang memenuhi syarat terlebih dahulu, lalu
     * mengacak dengan ORDER BY NEWID(). Pemanggilan tetap diulang beberapa
     * kali sebagai pengaman bila sebagian hasil harus dibuang (mis. ID yang
     * sudah dipakai pada penarikan sebelumnya).
     */
    public function getWinnerCandidates(int $idLokasi, ?string $idKecamatan, int $required, int $maxAttempts = 10, bool $isBbnkb = false, array $excludeIds = []): Collection
    {
        $required = max($required, 1);
        $collected = collect();
        $emptyAttempts = 0;

        for ($attempt = 0; $attempt < $maxAttempts && $collected->count() < $required; $attempt++) {
            $rows = collect($this->getWinner($idLokasi, $idKecamatan, $required, $isBbnkb));
            $before = $collected->count();

            $collected = $collected
                ->merge($rows)
                ->reject(fn($row) => in_array((int) $row->id, $excludeIds, true))
                ->unique('id')
                ->values();

            if ($collected->count() === $before) {
                if (++$emptyAttempts >= 3) {
                    break;
                }
            } else {
                $emptyAttempts = 0;
            }
        }

        return $collected;
    }

    public function getSamsatId(string $samsatName): ?int
    {
        $normalizedSamsatName = $this->normalizeSamsatName($samsatName);

        return $this->samsatIdMap()[$normalizedSamsatName] ?? null;
    }

    public function getSamsatIds(string $samsatName): array
    {
        $names = collect($this->parseSamsatNames($samsatName))
            ->map(fn($name) => $this->getSamsatId($name))
            ->filter()
            ->unique()
            ->values();

        return $names->all();
    }

    /**
     * Pengundian biasa per Samsat/wilayah.
     *
     * Setiap Samsat memakai SP getWinner(id_lokasi, '', jumlah) sehingga
     * pemenang berasal dari seluruh kecamatan pada Samsat tersebut.
     * Hasil antar Samsat digabung tanpa duplikasi lalu dipetakan ke
     * daftar hadiah yang dipilih.
     */
    public function pickDraw(
        array $prizeIds,
        array $samsats = [],
        int $totalWinnerPerSamsat = 5,
        bool $isDemo = true,
        bool $isBbnkb = false
    ): array {
        $prizes = Prize::whereIn('id', $prizeIds)->get();
        $totalWinner = $totalWinnerPerSamsat * count($samsats);
        $totalAvailable = $prizes->sum(fn($prize) => $prize->available_quantity);

        if ($totalAvailable < $totalWinner) {
            return [
                'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$totalWinner}). Pilih lebih banyak hadiah atau kurangi jumlah Samsat.",
                'status'  => 422,
            ];
        }

        $draw = null;
        if (!$isDemo) {
            $draw = Draw::create([
                'prize_id'          => $prizeIds[0],
                'prize_category_id' => $prizes->first()?->prize_category_id,
                'quantity'          => $totalWinner,
            ]);
        }

        $prizePool = $this->buildPrizePool($prizes, $totalWinner);
        $selectedVehicles = [];
        $selectedVehicleIds = [];

        foreach ($samsats as $samsat) {
            $samsatIds = $this->getSamsatIds($samsat);

            if (empty($samsatIds)) {
                $draw?->delete();

                return [
                    'message' => "Samsat {$samsat} tidak ditemukan di konfigurasi UPPD.",
                    'status'  => 422,
                ];
            }

            $configError = $this->validateConfiguredSamsatIds([$samsat => $samsatIds]);

            if ($configError !== null) {
                $draw?->delete();

                return [
                    'message' => $configError,
                    'status'  => 422,
                ];
            }

            $spWinners = collect($samsatIds)
                ->flatMap(fn($samsatId) => $this->getWinnerCandidates($samsatId, '', $totalWinnerPerSamsat, 10, $isBbnkb))
                ->unique('id')
                ->shuffle()
                ->take($totalWinnerPerSamsat)
                ->values();

            if ($spWinners->count() < $totalWinnerPerSamsat) {
                $draw?->delete();

                return [
                    'message' => "Tidak cukup kendaraan tersedia di Samsat {$samsat}. Tersedia: {$spWinners->count()}, dibutuhkan: {$totalWinnerPerSamsat}.",
                    'status'  => 422,
                ];
            }

            $pickedIds = $spWinners
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if ($pickedIds->count() < $totalWinnerPerSamsat) {
                $draw?->delete();

                return [
                    'message' => "Hasil SP untuk Samsat {$samsat} tidak valid atau mengandung kendaraan duplikat.",
                    'status'  => 422,
                ];
            }

            $duplicateWinnerExists = Winner::whereIn('kendaraan_id', $pickedIds)->exists();
            $duplicateInCurrentDraw = $pickedIds->intersect($selectedVehicleIds)->isNotEmpty();

            if ($duplicateWinnerExists || $duplicateInCurrentDraw) {
                $draw?->delete();

                return [
                    'message' => "Hasil SP untuk Samsat {$samsat} mengandung kendaraan yang sudah menang.",
                    'status'  => 422,
                ];
            }

            $vehicles = Kendaraan::whereIn('id', $pickedIds)->get()->keyBy('id');

            if ($vehicles->count() < $totalWinnerPerSamsat) {
                $draw?->delete();

                return [
                    'message' => "Hasil SP untuk Samsat {$samsat} tidak cocok dengan data kendaraan.",
                    'status'  => 422,
                ];
            }

            foreach ($pickedIds as $pickedId) {
                $vehicle = $vehicles[$pickedId];
                $vehicle->lokasi = $samsat;

                $selectedVehicles[] = $vehicle;
                $selectedVehicleIds[] = $pickedId;
            }
        }

        foreach ($selectedVehicles as $index => $vehicle) {
            $prizeId = $prizePool[$index];

            if (!$isDemo && $draw) {
                $this->saveWinner($draw->id, $vehicle->id, $vehicle->id_kendaraan, $prizeId);
            }

            $vehicle->prize = $prizes->firstWhere('id', $prizeId);
        }

        $response = ['data' => $selectedVehicles];

        if ($draw) {
            $response['draw_id'] = $draw->id;
        }

        return $response;
    }

    /**
     * Susun daftar hadiah secara acak sesuai stok masing-masing hadiah.
     *
     * @return array<int, int>
     */
    public function buildPrizePool(Collection $prizes, int $required): array
    {
        $prizePool = [];

        foreach ($prizes as $prize) {
            $quantity = min($prize->available_quantity, $required - count($prizePool));

            for ($i = 0; $i < $quantity; $i++) {
                $prizePool[] = $prize->id;

                if (count($prizePool) >= $required) {
                    break;
                }
            }

            if (count($prizePool) >= $required) {
                break;
            }
        }

        shuffle($prizePool);

        return $prizePool;
    }

    /**
     * ID lokasi/Samsat yang dikonfigurasi pada APP_ID_LOKASI.
     *
     * @return array<int, int>
     */
    protected function configuredSamsatIds(): array
    {
        return collect(config('wilayah.id_lokasi', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Validasi silang ID Samsat hasil mapping dengan APP_ID_LOKASI.
     *
     * Bila APP_ID_LOKASI diisi, seluruh ID Samsat yang dipakai harus termasuk
     * di dalamnya. Ini mencegah pengundian memakai id_lokasi yang salah.
     *
     * @param  array<string, array<int, int>>  $samsatIdsByLocation
     */
    protected function validateConfiguredSamsatIds(array $samsatIdsByLocation): ?string
    {
        $configured = $this->configuredSamsatIds();

        if (empty($configured)) {
            return null;
        }

        foreach ($samsatIdsByLocation as $location => $samsatIds) {
            $samsatIds = collect($samsatIds)
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $unexpected = array_values(array_diff($samsatIds, $configured));

            if (!empty($unexpected)) {
                return 'APP_ID_LOKASI (' . implode(',', $configured) . ') tidak cocok dengan Samsat untuk '
                    . "lokasi {$location} (id_lokasi " . implode(',', $samsatIds) . '). '
                    . 'Periksa kembali APP_ID_LOKASI pada .env dan mapping wilayah.';
            }
        }

        return null;
    }

    protected function normalizeSamsatName(string $name): string
    {
        $name = trim(str_replace(['Kab.', 'Kota'], '', $name));

        return strtoupper(preg_replace('/\s+/', ' ', $name));
    }

    protected function parseSamsatNames(string $samsatName): array
    {
        $normalizedSeparator = str_replace(['â†’', '→'], '->', $samsatName);
        $normalizedSeparator = str_replace(["\xC3\xA2\xE2\x80\xA0\xE2\x80\x99", "\xE2\x86\x92"], '->', $normalizedSeparator);
        $parts = explode('->', $normalizedSeparator, 2);
        $names = [trim($parts[0])];

        if (isset($parts[1])) {
            foreach (explode(',', $parts[1]) as $subName) {
                $names[] = trim($subName);
            }
        }

        return array_values(array_filter($names));
    }

    protected function samsatIdMap(): array
    {
        if ($this->samsatIdMap !== null) {
            return $this->samsatIdMap;
        }

        $this->samsatIdMap = collect(config('uppd', []))
            ->mapWithKeys(fn($name, $id) => [
                $this->normalizeSamsatName($name) => (int) $id,
            ])
            ->all();

        return $this->samsatIdMap;
    }

    protected function saveWinner(int $drawId, int $vehicleId, int $idKendaraan, int $prizeId): void
    {
        Winner::create([
            'draw_id'      => $drawId,
            'kendaraan_id' => $vehicleId,
            'id_kendaraan' => $idKendaraan,
            'prize_id'     => $prizeId,
        ]);
    }
}
