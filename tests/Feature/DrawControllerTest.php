<?php

namespace Tests\Feature;

use App\Models\Prize;
use App\Models\PrizeCategory;
use Database\Seeders\PrizeCategorySeeder;
use Database\Seeders\PrizeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Struktur kategori & hadiah saat ini:
     * - Hadiah Lainnya (level 1): Tabungan Rp 2,5 jt.
     * - Grand Prize (level 2): Tabungan Rp 15 jt, Rp 10 jt, Rp 7,5 jt,
     *   dan 2 unit Motor Honda Beat.
     * php artisan test --filter=DrawControllerTest
     */
    public function test_prize_data_is_seeded(): void
    {
        $this->seed(PrizeCategorySeeder::class);
        $this->seed(PrizeSeeder::class);

        // Kategori Emas sudah dihapus, tinggal dua kategori.
        $this->assertSame(2, PrizeCategory::count());
        $this->assertSame(2, PrizeCategory::where('name', 'Grand Prize')->value('level'));
        $this->assertSame(1, PrizeCategory::where('name', 'Hadiah Lainnya')->value('level'));
        $this->assertSame(5, Prize::count());
        $this->assertSame(185, Prize::where('name', 'Tabungan Rp 2,5 jt')->value('quantity'));
        $this->assertSame(1, Prize::where('name', 'Tabungan Rp 15 jt')->value('quantity'));
        $this->assertSame(1, Prize::where('name', 'Tabungan Rp 10 jt')->value('quantity'));
        $this->assertSame(1, Prize::where('name', 'Tabungan Rp 7,5 jt')->value('quantity'));
        $this->assertSame(2, Prize::where('name', 'Motor Honda Beat')->value('quantity'));

        // Tidak ada lagi hadiah emas.
        $this->assertSame(0, Prize::where('name', 'like', 'Emas%')->count());
    }
}
