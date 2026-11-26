<?php

use App\Support\GetWinnerProcedure;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Pastikan stored procedure pengundian selalu ikut dibuat saat project
     * di-pull ke laptop lain lalu menjalankan `php artisan migrate`.
     *
     * Pada SQL Server, migrasi ini membuat:
     *   getWinner(@id_lokasi_samsat, @id_kecamatan, @length, @roda = 0)
     *   getWinnerBbn(@id_lokasi_samsat, @id_kecamatan, @length)
     *
     * Parameter @roda dipertahankan agar signature tetap kompatibel dengan
     * spgetwinner.txt versi lama. Implementasinya tetap memakai versi baru
     * (filter Samsat/kecamatan dulu, lalu ORDER BY NEWID()), sehingga tidak
     * memakai sampling ID acak yang bisa menghasilkan ERROR/INFINITY LOOP.
     */
    public function up(): void
    {
        GetWinnerProcedure::create();
    }

    public function down(): void
    {
        GetWinnerProcedure::drop();
    }
};
