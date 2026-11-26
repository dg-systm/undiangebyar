<?php

namespace Database\Seeders;

use App\Models\Prize;
use App\Traits\QuerySqlsrvTrait;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrizeSeeder extends Seeder
{
    use QuerySqlsrvTrait;

    public function run(): void
    {
        $data = [
            [
                'prize_category_id' => 1,
                'name' => 'Tabungan Rp 2,5 jt',
                'description' => 'Tabungan uang di Bank Jateng senilai Rp 2.500.000',
                'quantity' => 185
            ],
            [
                'prize_category_id' => 2,
                'name' => 'Tabungan Rp 15 jt',
                'description' => 'Tabungan uang di Bank Jateng senilai Rp 15.000.000',
                'quantity' => 1
            ],
            [
                'prize_category_id' => 2,
                'name' => 'Tabungan Rp 10 jt',
                'description' => 'Tabungan uang di Bank Jateng senilai Rp 10.000.000',
                'quantity' => 1
            ],
            [
                'prize_category_id' => 2,
                'name' => 'Tabungan Rp 7,5 jt',
                'description' => 'Tabungan uang di Bank Jateng senilai Rp 7.500.000',
                'quantity' => 1
            ],
            [
                'prize_category_id' => 2,
                'name' => 'Motor Honda Beat',
                'description' => 'Motor Honda Beat (2 unit)',
                'quantity' => 2
            ],
        ];

        foreach ($data as $value) {
            Prize::create($value);
        }
        // $this->withIdentityInsert('dbo.prizes', function () use ($data) {
        // });
    }
}
