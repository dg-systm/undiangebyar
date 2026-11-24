<?php

namespace App\Filament\Widgets;

use App\Models\Kendaraan;
use App\Support\Wilayah;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatistikKendaraanWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Sumber data wilayah: config/coordinator.php (melalui helper Wilayah).
        // APP_KABKOTA kosong = cakupan seluruh Jawa Tengah.
        $active = trim((string) config('wilayah.active'));
        $isProvince = $active === '';

        $info = $isProvince ? Wilayah::province() : Wilayah::find($active);

        if (!$info['found']) {
            $isProvince = true;
            $info = Wilayah::province();
        }

        $wilayahName = $isProvince
            ? config('wilayah.province_name', 'Provinsi Jawa Tengah')
            : ($info['name'] ?: 'Wilayah');

        // Kecamatan & kelurahan mengikuti daftar mapping wilayah.
        $totalKecamatan = $info['kecamatan_count'];
        $totalKelurahan = $info['kelurahan_count'];

        // Total kendaraan dibatasi ke Samsat wilayah aktif; untuk cakupan
        // seluruh Jawa Tengah, semua kendaraan dihitung. Hasil count
        // di-cache sebentar karena tabel kendaraan berisi ratusan ribu baris.
        $samsatIds = $isProvince ? [] : $info['samsat_ids'];
        $cacheKey = 'widget.kendaraan_count.' . md5($isProvince ? 'province' : implode(',', $samsatIds));

        $totalKendaraan = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn (): int => $this->kendaraanWilayahQuery($samsatIds)->count()
        );

        return [
            Stat::make('Total Kendaraan', number_format($totalKendaraan, 0, ',', '.'))
                ->description("Kendaraan di {$wilayahName}")
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),

            Stat::make('Kecamatan', number_format($totalKecamatan, 0, ',', '.'))
                ->description("Kecamatan di {$wilayahName}")
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('success'),

            Stat::make('Kelurahan', number_format($totalKelurahan, 0, ',', '.'))
                ->description("Kelurahan/desa di {$wilayahName}")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('warning'),
        ];
    }

    /**
     * Query kendaraan yang dibatasi ke Samsat wilayah aktif.
     * Bila daftar Samsat kosong (cakupan seluruh Jawa Tengah), semua
     * kendaraan dihitung.
     */
    protected function kendaraanWilayahQuery(array $samsatIds)
    {
        $query = Kendaraan::query();

        if (!empty($samsatIds)) {
            $query->whereIn('id_lokasi', $samsatIds);
        }

        return $query;
    }
}
