<?php

use App\Support\GetWinnerProcedure;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Pastikan stored procedure pengundian memakai versi terbaru.
     *
     * Versi baru memfilter kendaraan berdasarkan Samsat/kecamatan terlebih
     * dahulu, lalu mengacak dengan ORDER BY NEWID(). Ini mencegah undian
     * gagal pada Samsat yang jumlah kendaraannya sedikit.
     *
     * Sekaligus membuat getWinnerBbn agar kategori BBNKB tetap berfungsi
     * ketika database dibangun ulang.
     */
    public function up(): void
    {
        GetWinnerProcedure::drop();
        GetWinnerProcedure::create();
    }

    public function down(): void
    {
        GetWinnerProcedure::drop();
    }
};
