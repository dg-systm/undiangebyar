<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Wilayah Undian
|--------------------------------------------------------------------------
|
| Aplikasi ini dipakai untuk seluruh Provinsi Jawa Tengah.
|
| Daftar lengkap koordinator (Plat), Samsat, kecamatan, dan kelurahan
| bersumber dari file config/coordinator.php yang dibaca melalui
| App\Support\Wilayah.
|
| Alur pengundian aplikasi:
| - Undian biasa : per koordinator/wilayah (Samsat), di halaman utama.
| - Grand Prize  : global semua kecamatan, jumlah pemenang fleksibel.
|
| Tidak ada pemilihan mode pengundian di admin. Nilai .env di bawah
| bersifat opsional dan langsung dipakai oleh halaman undian.
|
| Catatan: jangan jalankan `php artisan config:cache` setelah mengubah
| .env tanpa menjalankan `php artisan config:clear` terlebih dahulu.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Wilayah Aktif (opsional)
    |--------------------------------------------------------------------------
    |
    | Kosongkan APP_KABKOTA untuk memakai seluruh Jawa Tengah (pilihan
    | koordinator & Samsat dari semua wilayah). Isi hanya bila aplikasi
    | sengaja dibatasi ke satu Kabupaten/Kota.
    |
    */
    'active' => trim((string) env('APP_KABKOTA', '')),

    'province_name' => 'Provinsi Jawa Tengah',

    /*
    |--------------------------------------------------------------------------
    | ID Lokasi / Samsat (validasi silang, opsional)
    |--------------------------------------------------------------------------
    |
    | Nilai kolom kendaraans.id_lokasi yang diizinkan. Kosongkan untuk
    | seluruh Jawa Tengah. Bila diisi, pengundian hanya boleh memakai
    | ID Samsat tersebut.
    |
    | Bila wilayah memiliki lebih dari satu Samsat, isi dengan pemisah koma,
    | mis. APP_ID_LOKASI=1,2,3.
    |
    */
    'id_lokasi' => array_values(array_unique(array_filter(array_map(
        'intval',
        explode(',', (string) env('APP_ID_LOKASI', ''))
    )))),

    // Jumlah pemenang per lokasi/Samsat untuk 1 halaman (bawaan)
    'winners_per_page' => max(1, (int) env('APP_WINNERS_PER_PAGE', 5)),

];
