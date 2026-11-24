<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

class ImportJurnalKendaraan extends Command
{
    protected $signature = 'kendaraan:import-jurnal
        {file? : Path file CSV jurnal (default: storage/app/jurnal_laporan.csv)}
        {--limit=0 : Hanya baca N baris data (0 = semua)}
        {--no-fill-missing : Jangan buat kendaraan dummy untuk Samsat yang belum punya data}
        {--skip-index : Jangan buat index setelah impor}
        {--keep-staging : Jangan hapus tabel staging setelah selesai}';

    protected $description = 'Impor data kendaraan dari CSV jurnal Bapenda ke tabel kendaraans (SQL Server)';

    protected const STAGING = '_jurnal_kendaraan_import';

    protected const KECAMATAN_MAP = '_jurnal_kecamatan_map';

    protected const MIN_VEHICLES_PER_SAMSAT = 100;

    protected const FILL_COUNT_PER_SAMSAT = 500;

    public function handle(): int
    {
        $file = $this->argument('file') ?: storage_path('app/jurnal_laporan.csv');

        if (! is_file($file)) {
            $this->error("File CSV tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        if (DB::connection()->getDriverName() !== 'sqlsrv') {
            $this->error('Impor ini membutuhkan SQL Server (driver sqlsrv) dan utilitas bcp.');

            return self::FAILURE;
        }

        $limit = max(0, (int) $this->option('limit'));
        $errorFile = storage_path('logs/bcp-jurnal-errors.log');

        File::ensureDirectoryExists(dirname($errorFile));
        @unlink($errorFile);

        $this->info('Database : '.DB::connection()->getDatabaseName());
        $this->info('File CSV : '.$file);
        $this->info('Limit    : '.($limit > 0 ? "{$limit} baris" : 'semua baris'));

        try {
            $this->createStagingTables();
            $this->runBcp($file, $limit, $errorFile);

            $stagingCount = (int) DB::table(self::STAGING)->count();
            $this->info("Baris CSV masuk staging: {$stagingCount}");

            if ($stagingCount === 0) {
                throw new RuntimeException('Tidak ada baris yang berhasil dimasukkan ke staging.');
            }

            $before = (int) DB::table('kendaraans')->count();
            $this->insertKendaraan();
            $after = (int) DB::table('kendaraans')->count();
            $this->info('Kendaraan baru diimpor: '.($after - $before));

            if (! $this->option('no-fill-missing') && $limit === 0) {
                $filled = $this->fillMissingSamsat();

                if ($filled > 0) {
                    $this->info("Kendaraan dummy untuk Samsat kosong: {$filled}");
                }
            }

            if (! $this->option('skip-index')) {
                try {
                    $this->ensureIndexes();
                    $this->info('Index dan statistik diperbarui.');
                } catch (Throwable $e) {
                    $this->warn('Index/statistik gagal dibuat: '.$e->getMessage());
                }
            }

            // Total kendaraan pada dashboard di-cache; data berubah drastis
            // setelah impor, jadi cache perlu dikosongkan.
            try {
                Cache::flush();
            } catch (Throwable $e) {
                $this->warn('Cache gagal dikosongkan: '.$e->getMessage());
            }

            $this->showSummary();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            if (! $this->option('keep-staging')) {
                $this->dropTable(self::STAGING);
                $this->dropTable(self::KECAMATAN_MAP);
            }
        }

        return self::SUCCESS;
    }

    protected function createStagingTables(): void
    {
        $this->dropTable(self::STAGING);
        $this->dropTable(self::KECAMATAN_MAP);

        // Semua kolom staging memakai NVARCHAR(MAX). Sebagian baris CSV
        // memiliki tanda ";" di dalam alamat/nama sehingga jumlah kolom
        // bergeser; lebar MAX mencegah bcp berhenti karena truncation.
        $columns = array_map(
            fn (int $i) => "c{$i} NVARCHAR(MAX) NULL",
            range(1, 42)
        );

        DB::statement('CREATE TABLE dbo.['.self::STAGING.'] ('.implode(', ', $columns).')');

        DB::statement('
            CREATE TABLE dbo.['.self::KECAMATAN_MAP.'] (
                kode NVARCHAR(10) NOT NULL PRIMARY KEY,
                nama NVARCHAR(150) NOT NULL,
                id_lokasi INT NOT NULL
            )
        ');

        $rows = [];

        foreach (config('coordinator', []) as $plat => $info) {
            foreach (($info['samsat'] ?? []) as $samsat) {
                $idLokasi = isset($samsat['id_lokasi']) ? (int) $samsat['id_lokasi'] : null;

                if (! $idLokasi) {
                    continue;
                }

                foreach (($samsat['kecamatan'] ?? []) as $kode => $kecamatan) {
                    $kode = (string) $kode;

                    if (isset($rows[$kode])) {
                        continue;
                    }

                    $rows[$kode] = [
                        'kode' => $kode,
                        'nama' => $this->normalizeName($kecamatan['nama'] ?? $kode),
                        'id_lokasi' => $idLokasi,
                    ];
                }
            }
        }

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            DB::table(self::KECAMATAN_MAP)->insert($chunk);
        }

        DB::statement('CREATE INDEX IX_'.self::KECAMATAN_MAP.'_nama_lokasi ON dbo.['.self::KECAMATAN_MAP.'] (nama, id_lokasi)');
    }

    protected function runBcp(string $file, int $limit, string $errorFile): void
    {
        $connection = config('database.connections.sqlsrv');
        $host = trim((string) ($connection['host'] ?? 'localhost'));
        $port = trim((string) ($connection['port'] ?? ''));
        $server = $port !== '' && ! str_contains($host, ',')
            ? "tcp:{$host},{$port}"
            : "tcp:{$host}";

        $command = [
            'bcp',
            'dbo.'.self::STAGING,
            'in',
            $file,
            '-S', $server,
            '-d', (string) $connection['database'],
            '-c',
            '-C', '65001',
            '-t', ';',
            '-r', '\n',
            '-F', '2',
            '-b', '200000',
            '-e', $errorFile,
        ];

        if ($limit > 0) {
            $command[] = '-L';
            $command[] = (string) ($limit + 1); // baris 1 adalah header
        }

        $username = trim((string) ($connection['username'] ?? ''));

        if ($username === '') {
            $command[] = '-T';
        } else {
            $command[] = '-U';
            $command[] = $username;
            $command[] = '-P';
            $command[] = (string) ($connection['password'] ?? '');
        }

        if (filter_var($connection['trust_server_certificate'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $command[] = '-u';
        }

        $result = Process::timeout(1800)->run($command);

        if ($result->output() !== '') {
            $this->line(trim($result->output()));
        }

        if (is_file($errorFile) && filesize($errorFile) > 0) {
            $errors = File::get($errorFile);

            throw new RuntimeException("bcp melaporkan error:\n".substr($errors, 0, 4000));
        }

        if (is_file($errorFile)) {
            @unlink($errorFile);
        }

        if ($result->failed()) {
            throw new RuntimeException(
                "bcp gagal (exit code {$result->exitCode()}):\n"
                .trim($result->errorOutput()."\n".$result->output())
            );
        }
    }

    protected function insertKendaraan(): void
    {
        $locationIdCase = $this->locationCase(false);
        $locationNameCase = $this->locationCase(true);

        $sql = <<<SQL
;WITH src AS (
    SELECT
        LTRIM(RTRIM(c.c1)) AS id_kendaraan,
        UPPER(LTRIM(RTRIM(c.c2))) AS no_polisi,
        COALESCE(NULLIF(LTRIM(RTRIM(c.c28)), N''), N'-') AS nama,
        COALESCE(NULLIF(LTRIM(RTRIM(c.c29)), N''), N'-') AS alamat,
        COALESCE(km.kode, kmn.kode) AS id_kecamatan,
        CASE
            WHEN km.kode IS NOT NULL
                 AND UPPER(LTRIM(RTRIM(c.c36))) <> N'LAIN-LAIN'
                THEN k.kel
            ELSE NULL
        END AS id_kelurahan,
        loc.id_lokasi AS id_lokasi,
        loc.lokasi AS lokasi,
        CONCAT(N'9825', RIGHT(CONCAT(N'000000000000', LTRIM(RTRIM(c.c1))), 12)) AS id_billing,
        w.id_warna AS id_warna_tnkb,
        w.warna AS warna_tnkb,
        f.id_fungsi AS id_fungsi_kend,
        f.fungsi AS fungsi_kend,
        rd.roda AS roda,
        reg.id_pendaftaran AS id_pendaftaran,
        reg.pendaftaran AS pendaftaran,
        d.tgl_daftar AS tgl_daftar,
        d.tgl_daftar AS tgl_bayar,
        DATEADD(YEAR, 1, d.tgl_daftar) AS tgl_jatuh_tempo,
        DATEADD(YEAR, 3, d.tgl_daftar) AS tgl_akhir_stnk,
        ROW_NUMBER() OVER (PARTITION BY LTRIM(RTRIM(c.c1)) ORDER BY (SELECT 0)) AS rn
    FROM dbo.[{$this->stagingTable()}] AS c
    CROSS APPLY (
        SELECT
            {$locationIdCase} AS id_lokasi,
            {$locationNameCase} AS lokasi
    ) AS loc
    CROSS APPLY (
        SELECT
            CASE
                WHEN LTRIM(RTRIM(c.c35)) NOT LIKE '%[^0-9]%'
                     AND LEN(LTRIM(RTRIM(c.c35))) BETWEEN 1 AND 7
                    THEN RIGHT(CONCAT('0000000', LTRIM(RTRIM(c.c35))), 7)
                ELSE NULL
            END AS kel
    ) AS k
    CROSS APPLY (
        SELECT
            CASE UPPER(LTRIM(RTRIM(c.c22)))
                WHEN N'HITAM/PUTIH' THEN 1
                WHEN N'PUTIH' THEN 2
                WHEN N'MERAH' THEN 3
                WHEN N'KUNING' THEN 4
                WHEN N'HITAM BIRU' THEN 5
                WHEN N'HITAM/BIRU' THEN 5
                WHEN N'MERAH BIRU' THEN 6
                WHEN N'MERAH/BIRU' THEN 6
                ELSE CASE
                    WHEN UPPER(c.c21) LIKE N'%MERAH%' THEN 3
                    WHEN UPPER(c.c21) LIKE N'%KUNING%' THEN 4
                    WHEN UPPER(c.c21) LIKE N'%PUTIH%' THEN 2
                    ELSE 1
                END
            END AS id_warna,
            CASE UPPER(LTRIM(RTRIM(c.c22)))
                WHEN N'HITAM/PUTIH' THEN N'HITAM/PUTIH'
                WHEN N'PUTIH' THEN N'PUTIH'
                WHEN N'MERAH' THEN N'MERAH'
                WHEN N'KUNING' THEN N'KUNING'
                WHEN N'HITAM BIRU' THEN N'HITAM/BIRU'
                WHEN N'HITAM/BIRU' THEN N'HITAM/BIRU'
                WHEN N'MERAH BIRU' THEN N'MERAH/BIRU'
                WHEN N'MERAH/BIRU' THEN N'MERAH/BIRU'
                ELSE CASE
                    WHEN UPPER(c.c21) LIKE N'%MERAH%' THEN N'MERAH/PUTIH'
                    WHEN UPPER(c.c21) LIKE N'%KUNING%' THEN N'KUNING/HITAM'
                    WHEN UPPER(c.c21) LIKE N'%PUTIH%' THEN N'PUTIH'
                    ELSE N'HITAM/PUTIH'
                END
            END AS warna
    ) AS w
    CROSS APPLY (
        SELECT
            CASE UPPER(LTRIM(RTRIM(c.c17)))
                WHEN N'UMUM' THEN 2
                WHEN N'DINAS' THEN 3
                WHEN N'YAYASAN' THEN 4
                ELSE 1
            END AS id_fungsi,
            CASE UPPER(LTRIM(RTRIM(c.c17)))
                WHEN N'UMUM' THEN N'Umum'
                WHEN N'DINAS' THEN N'Dinas'
                WHEN N'YAYASAN' THEN N'Yayasan'
                ELSE N'Pribadi'
            END AS fungsi
    ) AS f
    CROSS APPLY (
        SELECT CASE
            WHEN TRY_CAST(LTRIM(RTRIM(c.c19)) AS INT) IS NULL THEN 2
            WHEN TRY_CAST(LTRIM(RTRIM(c.c19)) AS INT) IN (2, 4, 6, 8, 10) THEN TRY_CAST(LTRIM(RTRIM(c.c19)) AS INT)
            WHEN TRY_CAST(LTRIM(RTRIM(c.c19)) AS INT) <= 2 THEN 2
            ELSE 4
        END AS roda
    ) AS rd
    CROSS APPLY (
        SELECT ((CAST(CHECKSUM(CONCAT(LTRIM(RTRIM(c.c1)), N'|reg')) AS BIGINT) % 100) + 100) % 100 AS rand100
    ) AS pr
    CROSS APPLY (
        SELECT
            CASE
                WHEN pr.rand100 < 5 THEN 1
                WHEN pr.rand100 < 25 THEN 2
                WHEN pr.rand100 < 90 THEN 3
                ELSE 4
            END AS id_pendaftaran,
            CASE
                WHEN pr.rand100 < 5 THEN N'BBNKB'
                WHEN pr.rand100 < 25 THEN N'Mutasi'
                WHEN pr.rand100 < 90 THEN N'P.U./ Pembaharuan'
                ELSE N'Ganti STNK'
            END AS pendaftaran
    ) AS reg
    CROSS APPLY (
        SELECT CAST(DATEADD(
            DAY,
            ((CAST(CHECKSUM(CONCAT(LTRIM(RTRIM(c.c1)), N'|date')) AS BIGINT) % 181) + 181) % 181,
            CAST('2025-12-01' AS date)
        ) AS date) AS tgl_daftar
    ) AS d
    OUTER APPLY (
        SELECT TOP (1) m.kode
        FROM dbo.[{$this->kecamatanMapTable()}] AS m
        WHERE m.kode = LEFT(k.kel, 4)
          AND m.id_lokasi = loc.id_lokasi
        ORDER BY m.kode
    ) AS km
    OUTER APPLY (
        SELECT TOP (1) m.kode
        FROM dbo.[{$this->kecamatanMapTable()}] AS m
        WHERE m.nama = UPPER(LTRIM(RTRIM(c.c34)))
          AND m.id_lokasi = loc.id_lokasi
        ORDER BY m.kode
    ) AS kmn
    WHERE LTRIM(RTRIM(c.c1)) <> N''
      AND TRY_CAST(c.c1 AS BIGINT) IS NOT NULL
      AND LTRIM(RTRIM(c.c2)) <> N''
      AND loc.id_lokasi IS NOT NULL
)
INSERT INTO dbo.[kendaraans] (
    id_kendaraan, no_polisi, nama, alamat, id_kecamatan, id_kelurahan,
    id_lokasi, lokasi, id_lokasi_proses, lokasi_proses, id_billing,
    id_warna_tnkb, warna_tnkb, id_fungsi_kend, fungsi_kend, roda,
    id_pendaftaran, pendaftaran, tgl_daftar, tgl_bayar, tgl_jatuh_tempo,
    tgl_akhir_stnk, created_at, updated_at
)
SELECT
    id_kendaraan, no_polisi, nama, alamat, id_kecamatan, id_kelurahan,
    id_lokasi, lokasi, id_lokasi, lokasi, id_billing,
    id_warna_tnkb, warna_tnkb, id_fungsi_kend, fungsi_kend, roda,
    id_pendaftaran, pendaftaran, tgl_daftar, tgl_bayar, tgl_jatuh_tempo,
    tgl_akhir_stnk, GETDATE(), GETDATE()
FROM src
WHERE rn = 1
  AND NOT EXISTS (
      SELECT 1
      FROM dbo.[kendaraans] AS existing
      WHERE existing.id_kendaraan = src.id_kendaraan
  );
SQL;

        DB::statement($sql);
    }

    protected function fillMissingSamsat(): int
    {
        $counts = DB::table('kendaraans')
            ->select('id_lokasi', DB::raw('COUNT(*) AS total'))
            ->groupBy('id_lokasi')
            ->pluck('total', 'id_lokasi')
            ->map(fn ($total) => (int) $total)
            ->all();

        $wilayah = $this->wilayahByLocation();
        $filled = 0;

        foreach (config('uppd', []) as $id => $name) {
            $id = (int) $id;

            if ($id === 999 || ($counts[$id] ?? 0) >= self::MIN_VEHICLES_PER_SAMSAT) {
                continue;
            }

            $filled += $this->generateDummyVehicles($id, trim((string) $name), $wilayah[$id] ?? []);
        }

        return $filled;
    }

    /**
     * @return array<int, array<int, array{id: string, nama: string, kelurahan: array<int, string>}>>
     */
    protected function wilayahByLocation(): array
    {
        $wilayah = [];

        foreach (config('coordinator', []) as $plat => $info) {
            foreach (($info['samsat'] ?? []) as $samsat) {
                $idLokasi = isset($samsat['id_lokasi']) ? (int) $samsat['id_lokasi'] : null;

                if (! $idLokasi) {
                    continue;
                }

                foreach (($samsat['kecamatan'] ?? []) as $kode => $kecamatan) {
                    $wilayah[$idLokasi][] = [
                        'id' => (string) $kode,
                        'nama' => (string) ($kecamatan['nama'] ?? $kode),
                        'kelurahan' => array_map('strval', array_keys($kecamatan['kelurahan'] ?? [])),
                    ];
                }
            }
        }

        return $wilayah;
    }

    protected function generateDummyVehicles(int $idLokasi, string $namaLokasi, array $kecamatan): int
    {
        $this->line("Membuat kendaraan dummy untuk {$namaLokasi} (id_lokasi {$idLokasi})...");

        $existing = DB::table('kendaraans')
            ->where('id_lokasi', $idLokasi)
            ->pluck('id_kendaraan')
            ->flip();

        $firstNames = ['BUDI', 'SRI', 'AGUS', 'SITI', 'JOKO', 'DEWI', 'BAYU', 'RINA', 'DEDI', 'LESTARI', 'ANDI', 'NUR', 'EKO', 'TRI', 'HENDRA', 'YULI', 'RUDI', 'MEGA', 'FAJAR', 'WULAN'];
        $lastNames = ['SANTOSO', 'WIJAYA', 'PRATAMA', 'HIDAYAT', 'SETIAWAN', 'RAHAYU', 'SAPUTRA', 'HIDAYATI', 'NUGROHO', 'WULANDARI', 'PURNOMO', 'KUSUMA', 'MAULANA', 'ANGGRAINI', 'FIRDAUS', 'SARI'];
        $warna = [
            ['id' => 1, 'nama' => 'HITAM/PUTIH'],
            ['id' => 2, 'nama' => 'PUTIH'],
            ['id' => 3, 'nama' => 'MERAH'],
            ['id' => 4, 'nama' => 'KUNING'],
        ];

        $rows = [];
        $inserted = 0;
        $now = now()->toDateTimeString();
        $baseDate = Carbon::parse('2025-12-01');

        for ($i = 0; $i < self::FILL_COUNT_PER_SAMSAT; $i++) {
            $idKendaraan = (string) (990000000 + ($idLokasi * 100000) + $i);

            if ($existing->has($idKendaraan)) {
                continue;
            }

            $kec = $kecamatan[$i % max(count($kecamatan), 1)] ?? [
                'id' => str_pad((string) $idLokasi, 2, '0', STR_PAD_LEFT).'01',
                'nama' => $namaLokasi,
                'kelurahan' => [],
            ];
            $kelurahanList = $kec['kelurahan'] ?: [$kec['id'].'001'];
            $kelurahan = $kelurahanList[$i % count($kelurahanList)];

            $w = $warna[$i % count($warna)];
            $roda = $i % 5 === 0 ? 4 : 2;
            $reg = $i % 20 === 0 ? 1 : ($i % 5 === 0 ? 2 : ($i % 7 === 0 ? 4 : 3));
            $pendaftaran = [1 => 'BBNKB', 2 => 'Mutasi', 3 => 'P.U./ Pembaharuan', 4 => 'Ganti STNK'][$reg];
            $tglDaftar = $baseDate->copy()->addDays(($i + ($idLokasi * 3)) % 181);
            $nama = $firstNames[($i * 7) % count($firstNames)].' '.$lastNames[($i * 11) % count($lastNames)];
            $noPolisi = 'AA'.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT).'B'.chr(65 + ($idLokasi % 26));

            $rows[] = [
                'id_kendaraan' => $idKendaraan,
                'no_polisi' => $noPolisi,
                'nama' => $nama,
                'alamat' => "JL. RAYA {$kec['nama']} NO.".($i + 1).' RT/RW 00'.(($i % 9) + 1).'/00'.(($i % 7) + 1)." KEC. {$kec['nama']}",
                'id_kecamatan' => $kec['id'],
                'id_kelurahan' => $kelurahan,
                'id_lokasi' => $idLokasi,
                'lokasi' => $namaLokasi,
                'id_lokasi_proses' => $idLokasi,
                'lokasi_proses' => $namaLokasi,
                'id_billing' => '9825'.str_pad(substr($idKendaraan, -12), 12, '0', STR_PAD_LEFT),
                'id_warna_tnkb' => $w['id'],
                'warna_tnkb' => $w['nama'],
                'id_fungsi_kend' => 1,
                'fungsi_kend' => 'Pribadi',
                'roda' => $roda,
                'id_pendaftaran' => $reg,
                'pendaftaran' => $pendaftaran,
                'tgl_daftar' => $tglDaftar->toDateString(),
                'tgl_bayar' => $tglDaftar->toDateString(),
                'tgl_jatuh_tempo' => $tglDaftar->copy()->addYear()->toDateString(),
                'tgl_akhir_stnk' => $tglDaftar->copy()->addYears(3)->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) >= 50) {
                DB::table('kendaraans')->insert($rows);
                $inserted += count($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('kendaraans')->insert($rows);
            $inserted += count($rows);
        }

        return $inserted;
    }

    protected function ensureIndexes(): void
    {
        $indexes = [
            "IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_kendaraans_id_kendaraan' AND object_id = OBJECT_ID('dbo.kendaraans'))
                CREATE NONCLUSTERED INDEX IX_kendaraans_id_kendaraan ON dbo.kendaraans (id_kendaraan);",

            "IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_kendaraans_lokasi_kecamatan' AND object_id = OBJECT_ID('dbo.kendaraans'))
                CREATE NONCLUSTERED INDEX IX_kendaraans_lokasi_kecamatan ON dbo.kendaraans (id_lokasi, id_kecamatan)
                INCLUDE (id_kendaraan, no_polisi, roda, nama, alamat, lokasi, id_kelurahan, id_pendaftaran);",

            "IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_kendaraans_id_pendaftaran' AND object_id = OBJECT_ID('dbo.kendaraans'))
                CREATE NONCLUSTERED INDEX IX_kendaraans_id_pendaftaran ON dbo.kendaraans (id_pendaftaran)
                INCLUDE (id_lokasi, id_kecamatan, id_kendaraan, no_polisi, roda, nama, alamat, lokasi, id_kelurahan);",

            "IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_winners_id_kendaraan' AND object_id = OBJECT_ID('dbo.winners'))
                CREATE NONCLUSTERED INDEX IX_winners_id_kendaraan ON dbo.winners (id_kendaraan);",

            "IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_grand_prizes_id' AND object_id = OBJECT_ID('dbo.grand_prizes'))
                CREATE NONCLUSTERED INDEX IX_grand_prizes_id ON dbo.grand_prizes (id);",
        ];

        foreach ($indexes as $sql) {
            DB::statement($sql);
        }

        DB::statement('UPDATE STATISTICS dbo.kendaraans WITH FULLSCAN');
        DB::statement('UPDATE STATISTICS dbo.winners');
        DB::statement('UPDATE STATISTICS dbo.grand_prizes');
    }

    protected function showSummary(): void
    {
        $total = (int) DB::table('kendaraans')->count();
        $unique = (int) DB::table('kendaraans')->distinct()->count('id_kendaraan');
        $this->info("Total kendaraan: {$total} (id_kendaraan unik: {$unique})");

        $byLocation = DB::table('kendaraans')
            ->select('id_lokasi', DB::raw('MIN(lokasi) AS lokasi'), DB::raw('COUNT(*) AS jumlah'))
            ->groupBy('id_lokasi')
            ->orderBy('id_lokasi')
            ->get()
            ->map(fn ($row) => [(string) $row->id_lokasi, (string) $row->lokasi, (string) $row->jumlah])
            ->all();

        $this->table(['id_lokasi', 'Lokasi', 'Jumlah'], $byLocation);

        $counts = collect($byLocation)->mapWithKeys(fn ($row) => [(int) $row[0] => (int) $row[2]]);
        $missing = [];

        foreach (config('uppd', []) as $id => $name) {
            $id = (int) $id;

            if ($id !== 999 && ($counts[$id] ?? 0) === 0) {
                $missing[] = "{$id} - {$name}";
            }
        }

        if ($missing !== []) {
            $this->warn('Samsat tanpa data: '.implode(', ', $missing));
        } else {
            $this->info('Semua Samsat pada config/uppd.php memiliki data kendaraan.');
        }

        $this->newLine();
        $this->line('Uji cepat pengundian:');
        $this->line('  sqlcmd -S "tcp:127.0.0.1,1433" -E -C -d '.DB::connection()->getDatabaseName().' -Q "EXEC getWinner 29, \'\', 5"');
        $this->line('  sqlcmd -S "tcp:127.0.0.1,1433" -E -C -d '.DB::connection()->getDatabaseName().' -Q "EXEC getWinner 999, \'\', 10"');
    }

    protected function locationCase(bool $asName): string
    {
        $cases = collect(config('uppd', []))
            ->map(fn ($name, $id) => [(int) $id, trim((string) $name)])
            ->filter(fn ($row) => $row[0] > 0 && $row[0] !== 999 && $row[1] !== '')
            ->unique(fn ($row) => strtoupper($row[1]))
            ->sortBy(0);

        $sql = '';

        foreach ($cases as [$id, $name]) {
            $key = str_replace("'", "''", strtoupper($name));
            $value = $asName
                ? "N'".str_replace("'", "''", $name)."'"
                : (string) $id;

            $sql .= "WHEN N'{$key}' THEN {$value}\n";
        }

        return "CASE UPPER(LTRIM(RTRIM(c.c24)))\n{$sql}ELSE NULL END";
    }

    protected function normalizeName(?string $value): string
    {
        $value = strtoupper(trim((string) $value));

        return (string) preg_replace('/\s+/', ' ', $value);
    }

    protected function stagingTable(): string
    {
        return self::STAGING;
    }

    protected function kecamatanMapTable(): string
    {
        return self::KECAMATAN_MAP;
    }

    protected function dropTable(string $table): void
    {
        DB::statement("IF OBJECT_ID('dbo.[{$table}]', 'U') IS NOT NULL DROP TABLE dbo.[{$table}]");
    }
}
