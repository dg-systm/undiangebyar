<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Definisi stored procedure pengundian.
 *
 * Signature SP (harus sama dengan database undian_samsat):
 *   getWinner(@id_lokasi_samsat INT, @id_kecamatan VARCHAR(10), @length INT = 5, @roda INT = 0)
 *   getWinnerBbn(@id_lokasi_samsat INT, @id_kecamatan VARCHAR(10), @length INT = 5)
 *
 * - id_lokasi_samsat = 999  -> mode global (Grand Prize), id_kecamatan diabaikan.
 * - id_kecamatan = ''       -> semua kecamatan pada Samsat tersebut.
 * - roda = 0                -> semua roda; 4 = roda >= 4; 2 = roda < 4.
 *   Parameter ini opsional agar tetap kompatibel dengan pemanggil lama
 *   (signature spgetwinner.txt) tanpa mengubah perilaku aplikasi sekarang.
 *
 * Catatan performa:
 * Sebelumnya SQL Server mengambil sampel ID acak dari seluruh tabel lalu
 * memfilternya berdasarkan Samsat. Cara itu bisa mengembalikan lebih sedikit
 * kendaraan (bahkan nol) untuk Samsat kecil. Versi ini memfilter dulu baru
 * mengacak dengan ORDER BY NEWID(), sehingga hasil selalu diambil dari
 * kumpulan kendaraan yang benar-benar memenuhi syarat.
 */
class GetWinnerProcedure
{
    public static function create(?string $driver = null): void
    {
        $driver ??= DB::connection()->getDriverName();

        if ($driver === 'sqlsrv') {
            static::drop($driver);
            DB::unprepared(static::sqlServer());
            DB::unprepared(static::sqlServerBbn());

            return;
        }

        static::drop($driver);
        DB::unprepared(static::mySql());
    }

    public static function drop(?string $driver = null): void
    {
        $driver ??= DB::connection()->getDriverName();

        if ($driver === 'sqlsrv') {
            DB::unprepared("IF OBJECT_ID('dbo.getWinner', 'P') IS NOT NULL DROP PROCEDURE dbo.getWinner");
            DB::unprepared("IF OBJECT_ID('dbo.getWinnerBbn', 'P') IS NOT NULL DROP PROCEDURE dbo.getWinnerBbn");

            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS getWinner');
    }

    public static function sqlServer(): string
    {
        return "
            CREATE PROCEDURE [dbo].[getWinner]
                @id_lokasi_samsat INT
                ,@id_kecamatan VARCHAR(10)
                ,@length INT = 5
                ,@roda INT = 0
            AS
            BEGIN
                SET NOCOUNT ON;

                IF @length IS NULL OR @length < 1
                    SET @length = 5;

                IF @id_lokasi_samsat = 999
                    BEGIN
                        SELECT TOP (@length)
                            k.[id]
                            ,k.[id_kendaraan]
                            ,k.[no_polisi]
                            ,k.[roda]
                            ,k.[nama]
                            ,k.[alamat]
                            ,k.[lokasi]
                            ,k.[id_kecamatan]
                            ,k.[id_kelurahan]
                        FROM [dbo].[kendaraans] AS k
                        WHERE CASE
                                WHEN @roda = 0 THEN 0
                                WHEN k.[roda] >= 4 THEN 4
                                ELSE 2
                            END = @roda
                        AND NOT EXISTS (
                            SELECT 1
                            FROM [dbo].[winners] AS w
                            WHERE w.[id_kendaraan] = k.[id_kendaraan]
                        )
                        AND NOT EXISTS (
                            SELECT 1
                            FROM [dbo].[grand_prizes] AS g
                            WHERE g.[id] = TRY_CAST(k.[id_kendaraan] AS BIGINT)
                        )
                        ORDER BY NEWID();
                    END
                ELSE
                    BEGIN
                        SELECT TOP (@length)
                            k.[id]
                            ,k.[id_kendaraan]
                            ,k.[no_polisi]
                            ,k.[roda]
                            ,k.[nama]
                            ,k.[alamat]
                            ,k.[lokasi]
                            ,k.[id_kecamatan]
                            ,k.[id_kelurahan]
                        FROM [dbo].[kendaraans] AS k
                        WHERE k.[id_lokasi] = @id_lokasi_samsat
                            AND (
                                @id_kecamatan IS NULL
                                OR @id_kecamatan = ''
                                OR k.[id_kecamatan] LIKE @id_kecamatan
                            )
                            AND CASE
                                WHEN @roda = 0 THEN 0
                                WHEN k.[roda] >= 4 THEN 4
                                ELSE 2
                            END = @roda
                            AND NOT EXISTS (
                                SELECT 1
                                FROM [dbo].[winners] AS w
                                WHERE w.[id_kendaraan] = k.[id_kendaraan]
                            )
                            AND NOT EXISTS (
                                SELECT 1
                                FROM [dbo].[grand_prizes] AS g
                                WHERE g.[id] = TRY_CAST(k.[id_kendaraan] AS BIGINT)
                            )
                        ORDER BY NEWID();
                    END
            END";
    }

    /**
     * Pengundian khusus kategori BBNKB: hanya kendaraan dengan
     * id_pendaftaran = 1 (pendaftaran baru) yang boleh ikut diundi.
     */
    public static function sqlServerBbn(): string
    {
        return "
            CREATE PROCEDURE [dbo].[getWinnerBbn]
                @id_lokasi_samsat INT
                ,@id_kecamatan VARCHAR(10)
                ,@length INT = 5
            AS
            BEGIN
                SET NOCOUNT ON;

                IF @length IS NULL OR @length < 1
                    SET @length = 5;

                SELECT TOP (@length)
                    k.[id]
                    ,k.[id_kendaraan]
                    ,k.[no_polisi]
                    ,k.[roda]
                    ,k.[nama]
                    ,k.[alamat]
                    ,k.[lokasi]
                    ,k.[id_kecamatan]
                    ,k.[id_kelurahan]
                FROM [dbo].[kendaraans] AS k
                WHERE (
                        @id_lokasi_samsat = 999
                        OR k.[id_lokasi] = @id_lokasi_samsat
                    )
                    AND (
                        @id_lokasi_samsat = 999
                        OR @id_kecamatan IS NULL
                        OR @id_kecamatan = ''
                        OR k.[id_kecamatan] LIKE @id_kecamatan
                    )
                    AND k.[id_pendaftaran] = 1
                    AND NOT EXISTS (
                        SELECT 1
                        FROM [dbo].[winners] AS w
                        WHERE w.[id_kendaraan] = k.[id_kendaraan]
                    )
                    AND NOT EXISTS (
                        SELECT 1
                        FROM [dbo].[grand_prizes] AS g
                        WHERE g.[id] = TRY_CAST(k.[id_kendaraan] AS BIGINT)
                    )
                ORDER BY NEWID();
            END";
    }

    public static function mySql(): string
    {
        return "
            CREATE PROCEDURE getWinner(
                IN p_id_lokasi_samsat INT,
                IN p_id_kecamatan VARCHAR(10),
                IN p_length INT
            )
            BEGIN
                -- MySQL tidak mendukung default argument, tangani NULL secara manual.
                IF p_length IS NULL THEN
                    SET p_length = 5;
                END IF;

                IF p_id_lokasi_samsat = 999 THEN
                    SET @sql = '
                        SELECT
                            id,
                            id_kendaraan,
                            no_polisi,
                            roda,
                            nama,
                            alamat,
                            lokasi,
                            id_kecamatan,
                            id_kelurahan
                        FROM kendaraans
                        WHERE id_kendaraan NOT IN (SELECT id_kendaraan FROM winners)
                        AND id_kendaraan NOT IN (SELECT id FROM grand_prizes)
                        ORDER BY RAND()
                        LIMIT ?';

                    PREPARE stmt FROM @sql;
                    EXECUTE stmt USING p_length;
                    DEALLOCATE PREPARE stmt;
                ELSE
                    SET @sql = '
                        SELECT
                            id,
                            id_kendaraan,
                            no_polisi,
                            roda,
                            nama,
                            alamat,
                            lokasi,
                            id_kecamatan,
                            id_kelurahan
                        FROM kendaraans
                        WHERE id_lokasi = ?
                            AND id_kecamatan LIKE ?
                            AND id_kendaraan NOT IN (SELECT id_kendaraan FROM winners)
                            AND id_kendaraan NOT IN (SELECT id FROM grand_prizes)
                        ORDER BY RAND()
                        LIMIT ?';

                    SET @id_lokasi = p_id_lokasi_samsat;
                    SET @id_kecamatan = IF(p_id_kecamatan IS NULL OR p_id_kecamatan = '', '%', p_id_kecamatan);
                    SET @length = p_length;

                    PREPARE stmt FROM @sql;
                    EXECUTE stmt USING @id_lokasi, @id_kecamatan, @length;
                    DEALLOCATE PREPARE stmt;
                END IF;
            END";
    }
}
