<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sinkronisasi data hadiah:
 *
 * 1. Kategori "Emas" dihapus beserta seluruh hadiahnya.
 * 2. Hadiah Grand Prize diganti menjadi uang tabungan
 *    (Rp 15 jt, Rp 10 jt, Rp 7,5 jt) dan 2 unit Motor Honda Beat.
 * 3. Level kategori dinomori ulang: 1 = Hadiah Lainnya, 2 = Grand Prize.
 *
 * Migrasi ini aman dijalankan pada database kosong (belum di-seed) karena
 * seluruh operasi bersifat kondisional (tidak ada yang dihapus/diubah bila
 * datanya memang tidak ada).
 */
return new class extends Migration
{
    public function up(): void
    {
        $emasCategoryId = DB::table('prize_categories')->where('name', 'Emas')->value('id');
        $grandCategoryId = DB::table('prize_categories')->where('name', 'Grand Prize')->value('id');

        // Kumpulkan id hadiah lama yang akan diganti (Emas + Grand Prize lama).
        $stalePrizeIds = DB::table('prizes')
            ->where(function ($query) use ($emasCategoryId, $grandCategoryId) {
                $query->where('name', 'like', 'Emas%');

                if ($grandCategoryId !== null) {
                    $query->orWhere('prize_category_id', $grandCategoryId);
                }

                if ($emasCategoryId !== null) {
                    $query->orWhere('prize_category_id', $emasCategoryId);
                }
            })
            ->pluck('id')
            ->all();

        if (!empty($stalePrizeIds)) {
            // Winners me-rules cascade ke prize, namun draws tidak; bersihkan
            // secara eksplisit agar penghapusan prize tidak terblokir FK.
            DB::table('winners')->whereIn('prize_id', $stalePrizeIds)->delete();
            DB::table('draws')->whereIn('prize_id', $stalePrizeIds)->delete();
            DB::table('draws')->whereIn('prize_category_id', array_filter([
                $emasCategoryId,
                $grandCategoryId,
            ]))->delete();

            DB::table('prizes')->whereIn('id', $stalePrizeIds)->delete();
        }

        // Hapus kategori Emas.
        if ($emasCategoryId !== null) {
            DB::table('prize_categories')->where('id', $emasCategoryId)->delete();
        }

        // Nomori ulang level: Hadiah Lainnya = 1, Grand Prize = 2.
        DB::table('prize_categories')->where('name', 'Hadiah Lainnya')->update(['level' => 1]);

        if ($grandCategoryId !== null) {
            DB::table('prize_categories')->where('id', $grandCategoryId)->update(['level' => 2]);

            // Hadiah Grand Prize baru.
            $exists = DB::table('prizes')->where('prize_category_id', $grandCategoryId)->exists();

            if (!$exists) {
                DB::table('prizes')->insert([
                    [
                        'prize_category_id' => $grandCategoryId,
                        'name'              => 'Tabungan Rp 15 jt',
                        'description'       => 'Tabungan uang di Bank Jateng senilai Rp 15.000.000',
                        'quantity'          => 1,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ],
                    [
                        'prize_category_id' => $grandCategoryId,
                        'name'              => 'Tabungan Rp 10 jt',
                        'description'       => 'Tabungan uang di Bank Jateng senilai Rp 10.000.000',
                        'quantity'          => 1,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ],
                    [
                        'prize_category_id' => $grandCategoryId,
                        'name'              => 'Tabungan Rp 7,5 jt',
                        'description'       => 'Tabungan uang di Bank Jateng senilai Rp 7.500.000',
                        'quantity'          => 1,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ],
                    [
                        'prize_category_id' => $grandCategoryId,
                        'name'              => 'Motor Honda Beat',
                        'description'       => 'Motor Honda Beat (2 unit)',
                        'quantity'          => 2,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ],
                ]);
            }
        }
    }

    public function down(): void
    {
        // Pengembalian data hadiah tidak didukung (butuh data lama yang sudah
        // dihapus). Jalankan ulang seeder untuk memulihkan struktur awal.
    }
};
