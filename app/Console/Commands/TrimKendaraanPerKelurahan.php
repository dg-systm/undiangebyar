<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class TrimKendaraanPerKelurahan extends Command
{
    protected $signature = 'kendaraan:trim-per-kelurahan
        {--limit=100 : Maksimum kendaraan non-pemerintah per kelurahan}
        {--keep-null-kelurahan : Pertahankan maksimum N kendaraan per kecamatan untuk baris tanpa kode kelurahan}
        {--dry-run : Tampilkan ringkasan tanpa menghapus}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Hapus kendaraan pemerintah dan sisakan maksimal N kendaraan non-pemerintah per kelurahan';

    /**
     * Kendaraan pemerintah dikenali dari fungsi Dinas atau TNKB merah.
     * Keduanya hampir identik pada data Bapenda (11.953 dari 11.961 baris).
     */
    protected const GOV_FILTER = "(id_fungsi_kend = 3 OR UPPER(LTRIM(RTRIM(fungsi_kend))) = 'DINAS' OR UPPER(LTRIM(RTRIM(warna_tnkb))) LIKE 'MERAH%')";

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'sqlsrv') {
            $this->error('Command ini membutuhkan SQL Server (driver sqlsrv).');

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $keepNull = (bool) $this->option('keep-null-kelurahan');
        $dryRun = (bool) $this->option('dry-run');

        $total = (int) DB::table('kendaraans')->count();
        $winners = (int) DB::table('winners')->count();
        $government = (int) DB::selectOne(
            'SELECT COUNT(*) AS jumlah FROM dbo.kendaraans AS k WHERE '.self::GOV_FILTER
            .' AND NOT EXISTS (SELECT 1 FROM dbo.winners AS w WHERE w.kendaraan_id = k.id)'
        )->jumlah;

        $kelurahan = DB::selectOne('
            WITH kelompok AS (
                SELECT k.id_kelurahan, COUNT(*) AS jumlah
                FROM dbo.kendaraans AS k
                WHERE k.id_kelurahan IS NOT NULL
                  AND NOT '.self::GOV_FILTER.'
                  AND NOT EXISTS (SELECT 1 FROM dbo.winners AS w WHERE w.kendaraan_id = k.id)
                GROUP BY k.id_kelurahan
            )
            SELECT
                COUNT(*) AS jumlah_kelurahan,
                SUM(CASE WHEN jumlah > '.$limit.' THEN '.$limit.' ELSE jumlah END) AS disimpan,
                SUM(CASE WHEN jumlah > '.$limit.' THEN jumlah - '.$limit.' ELSE 0 END) AS dipangkas
            FROM kelompok
        ');

        $nullCount = (int) DB::selectOne(
            'SELECT COUNT(*) AS jumlah
             FROM dbo.kendaraans AS k
             WHERE k.id_kelurahan IS NULL
               AND NOT '.self::GOV_FILTER.'
               AND NOT EXISTS (SELECT 1 FROM dbo.winners AS w WHERE w.kendaraan_id = k.id)'
        )->jumlah;

        $nullTrim = $nullCount;

        if ($keepNull) {
            $nullKelompok = DB::selectOne('
                WITH kelompok AS (
                    SELECT k.id_lokasi, k.id_kecamatan, COUNT(*) AS jumlah
                    FROM dbo.kendaraans AS k
                    WHERE k.id_kelurahan IS NULL
                      AND NOT '.self::GOV_FILTER.'
                      AND NOT EXISTS (SELECT 1 FROM dbo.winners AS w WHERE w.kendaraan_id = k.id)
                    GROUP BY k.id_lokasi, k.id_kecamatan
                )
                SELECT SUM(CASE WHEN jumlah > '.$limit.' THEN jumlah - '.$limit.' ELSE 0 END) AS dipangkas
                FROM kelompok
            ');

            $nullTrim = (int) ($nullKelompok->dipangkas ?? 0);
        }

        $trimValid = (int) ($kelurahan->dipangkas ?? 0);
        $keepValid = (int) ($kelurahan->disimpan ?? 0);
        $deletions = $government + $trimValid + $nullTrim;
        $remaining = $total - $deletions;

        $this->table(
            ['Keterangan', 'Jumlah'],
            [
                ['Total kendaraan sekarang', number_format($total, 0, ',', '.')],
                ['Kendaraan pemerintah yang dihapus', number_format($government, 0, ',', '.')],
                ['Kelurahan dengan kode', number_format((int) ($kelurahan->jumlah_kelurahan ?? 0), 0, ',', '.')],
                ['Disimpan (maks '.$limit.'/kelurahan)', number_format($keepValid, 0, ',', '.')],
                ['Dihapus karena melebihi batas', number_format($trimValid, 0, ',', '.')],
                ['Tanpa kode kelurahan', number_format($nullCount, 0, ',', '.')],
                ['Dihapus dari tanpa kode kelurahan', number_format($nullTrim, 0, ',', '.')],
                ['Pemenang (dipertahankan)', number_format($winners, 0, ',', '.')],
                ['Total akan dihapus', number_format($deletions, 0, ',', '.')],
                ['Perkiraan sisa kendaraan', number_format($remaining, 0, ',', '.')],
            ]
        );

        if ($dryRun) {
            $this->info('Mode dry-run: tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        if ($deletions === 0) {
            $this->info('Tidak ada kendaraan yang perlu dihapus.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Hapus {$deletions} kendaraan sekarang?")) {
            $this->warn('Dibatalkan.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($limit, $keepNull): void {
                // 1. Hapus kendaraan pemerintah, kecuali yang sudah menjadi pemenang.
                DB::statement(
                    'DELETE k
                     FROM dbo.kendaraans AS k
                     WHERE '.self::GOV_FILTER.'
                       AND NOT EXISTS (SELECT 1 FROM dbo.winners AS w WHERE w.kendaraan_id = k.id)'
                );

                // 2. Sisakan maksimal N kendaraan non-pemenang per kelurahan.
                DB::statement('
                    ;WITH ranked AS (
                        SELECT
                            k.id,
                            ROW_NUMBER() OVER (PARTITION BY k.id_kelurahan ORDER BY k.id) AS rn
                        FROM dbo.kendaraans AS k
                        WHERE k.id_kelurahan IS NOT NULL
                          AND NOT EXISTS (SELECT 1 FROM dbo.winners AS w WHERE w.kendaraan_id = k.id)
                    )
                    DELETE k
                    FROM dbo.kendaraans AS k
                    INNER JOIN ranked AS r ON r.id = k.id
                    WHERE r.rn > '.$limit.'
                      AND NOT EXISTS (SELECT 1 FROM dbo.winners AS w WHERE w.kendaraan_id = k.id)
                ');

                // 3. Baris tanpa kode kelurahan.
                if ($keepNull) {
                    DB::statement('
                        ;WITH ranked AS (
                            SELECT
                                k.id,
                                ROW_NUMBER() OVER (PARTITION BY k.id_lokasi, k.id_kecamatan ORDER BY k.id) AS rn
                            FROM dbo.kendaraans AS k
                            WHERE k.id_kelurahan IS NULL
                              AND NOT EXISTS (SELECT 1 FROM dbo.winners AS w WHERE w.kendaraan_id = k.id)
                        )
                        DELETE k
                        FROM dbo.kendaraans AS k
                        INNER JOIN ranked AS r ON r.id = k.id
                        WHERE r.rn > '.$limit.'
                          AND NOT EXISTS (SELECT 1 FROM dbo.winners AS w WHERE w.kendaraan_id = k.id)
                    ');
                } else {
                    DB::statement('
                        DELETE k
                        FROM dbo.kendaraans AS k
                        WHERE k.id_kelurahan IS NULL
                          AND NOT EXISTS (SELECT 1 FROM dbo.winners AS w WHERE w.kendaraan_id = k.id)
                    ');
                }
            });
        } catch (Throwable $e) {
            $this->error('Gagal merampingkan data: '.$e->getMessage());

            return self::FAILURE;
        }

        DB::statement('UPDATE STATISTICS dbo.kendaraans WITH FULLSCAN');

        try {
            Cache::flush();
        } catch (Throwable $e) {
            $this->warn('Cache gagal dikosongkan: '.$e->getMessage());
        }

        $this->showSummary($limit);

        return self::SUCCESS;
    }

    protected function showSummary(int $limit): void
    {
        $total = (int) DB::table('kendaraans')->count();
        $unique = (int) DB::table('kendaraans')->distinct()->count('id_kendaraan');
        $null = (int) DB::table('kendaraans')->whereNull('id_kelurahan')->count();
        $government = (int) DB::selectOne(
            'SELECT COUNT(*) AS jumlah FROM dbo.kendaraans WHERE '.self::GOV_FILTER
        )->jumlah;

        $max = DB::selectOne('
            SELECT MAX(jumlah) AS maks
            FROM (
                SELECT COUNT(*) AS jumlah
                FROM dbo.kendaraans
                WHERE id_kelurahan IS NOT NULL
                GROUP BY id_kelurahan
            ) AS x
        ');

        $this->newLine();
        $this->info('Selesai.');
        $this->line('Total kendaraan      : '.number_format($total, 0, ',', '.'));
        $this->line('id_kendaraan unik    : '.number_format($unique, 0, ',', '.'));
        $this->line('Tanpa kode kelurahan : '.number_format($null, 0, ',', '.'));
        $this->line('Kendaraan pemerintah: '.number_format($government, 0, ',', '.'));
        $this->line('Maks per kelurahan   : '.number_format((int) ($max->maks ?? 0), 0, ',', '.').' (batas '.$limit.')');

        $byLocation = DB::table('kendaraans')
            ->select('id_lokasi', DB::raw('COUNT(*) AS jumlah'))
            ->groupBy('id_lokasi')
            ->orderBy('id_lokasi')
            ->pluck('jumlah', 'id_lokasi');

        $kosong = [];

        foreach (config('uppd', []) as $id => $name) {
            $id = (int) $id;

            if ($id !== 999 && (int) ($byLocation[$id] ?? 0) === 0) {
                $kosong[] = "{$id} - {$name}";
            }
        }

        if ($kosong !== []) {
            $this->warn('Samsat tanpa kendaraan: '.implode(', ', $kosong));
        } else {
            $this->info('Semua Samsat masih memiliki kendaraan.');
        }
    }
}
