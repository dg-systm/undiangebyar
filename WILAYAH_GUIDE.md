# Panduan Wilayah Undian (Seluruh Jawa Tengah)

Aplikasi ini dipakai untuk **seluruh Provinsi Jawa Tengah**, mencakup semua
koordinator (Plat H, K, AD, G, R, AA), Samsat, kabupaten/kota, dan kecamatan.

- **Tidak ada pemilihan mode pengundian di admin.** Halaman undian langsung
  memakai alur:
  - **Undian biasa** — per **koordinator/wilayah (Samsat)** di halaman utama.
  - **Grand Prize** — global semua kecamatan dengan jumlah pemenang fleksibel.

## 1. Pengaturan Wilayah di .env (Opsional)

Untuk cakupan seluruh Jawa Tengah, biarkan kedua nilai berikut **kosong**:

```env
APP_KABKOTA=
APP_ID_LOKASI=
APP_WINNERS_PER_PAGE=5
```

- `APP_KABKOTA` → kosong berarti seluruh Jawa Tengah. Bila diisi, aplikasi
  dibatasi ke satu Kabupaten/Kota (fitur opsional).
- `APP_ID_LOKASI` → kosong berarti semua Samsat boleh diundi. Bila diisi,
  pengundian hanya boleh memakai ID Samsat tersebut. Bisa diisi beberapa ID
  dengan pemisah koma, mis. `APP_ID_LOKASI=1,2,3`.
- `APP_WINNERS_PER_PAGE` → default jumlah pemenang per Samsat dalam 1
  halaman (masih bisa diubah dari sidebar saat aplikasi berjalan).

Setelah mengubah `.env`, jalankan:

```bash
php artisan config:clear
```

## 2. Alur Pengundian

### Undian Biasa (per koordinator/wilayah)

1. Buka halaman `/`.
2. Di sidebar, pilih **Koordinator Lokasi** dengan label wilayah seperti
   undianbapenda: **Semarang Raya, Pati Raya, Solo Raya, Pekalongan Raya,
   Banyumas Raya, Kedu Raya**.
3. Pilih **Samsat** yang ikut diundi (otomatis terpilih semua, bisa diubah).
   Samsat Pembantu digabung dengan induknya, mis. **Blora → Cepu** atau
   **Klaten → Prambanan, Delanggu**.
4. Pilih **Kategori Hadiah** dan **Hadiah**.
5. Atur **Jumlah Pemenang per Samsat** (bawaan dari `.env`).
6. Tekan **Simpan**, lalu **Start Pengundian!**.
7. Setiap Samsat menghasilkan pemenang sebanyak jumlah yang diatur. Pemenang
   diambil melalui SP `getWinner(id_lokasi, '', jumlah)` yang sudah ada.

Samsat yang sudah diundi ditandai centang dan otomatis tidak terpilih lagi
agar undian berikutnya tidak mengulang Samsat yang sama.

### Grand Prize (global semua kecamatan)

1. Buka halaman `/grand-prize` melalui tombol hadiah di pojok kanan atas.
2. Pilih **Kategori Hadiah** dan **Hadiah**.
3. Isi **Jumlah Pemenang** sesuai kebutuhan acara (fleksibel).
4. Tekan **Simpan**, lalu **Start Pengundian!**.

Grand Prize memakai SP `getWinner` mode global (`id_lokasi = 999`) dan tidak
memakai komposisi roda 2/roda 4.

## 3. Sumber Data Wilayah

Daftar lengkap **koordinator, Samsat, Kabupaten/Kota, Kecamatan, dan Kelurahan
se-Jawa Tengah** berasal dari file:

```
config/coordinator.php
```

Struktur file tersebut:

```
Plat -> Samsat -> Kota/Kabupaten -> Kecamatan -> Kelurahan
```

Data ini dibaca melalui helper `App\Support\Wilayah`, yang otomatis:
- menggabungkan seluruh koordinator dan Samsat,
- mencocokkan nama Kabupaten/Kota (tidak membedakan huruf besar/kecil),
- menghilangkan duplikasi dari Samsat Pembantu (data `induk`).

Daftar Kabupaten/Kota yang valid ada 35 (29 kabupaten + 6 kota) dan semuanya
sudah termuat di `config/coordinator.php`.

## 4. Alur Tampilan

1. Halaman pertama menampilkan **poster utama** (gambar full 1 halaman).
2. Terdapat **tombol Grand Prize**, tombol sidebar, dan tombol pengaturan
   tampilan di pojok kanan atas.
3. Buka sidebar untuk:
   - melihat cakupan wilayah (Jawa Tengah),
   - memilih **Koordinator Lokasi** lalu **Samsat** (Select All / per-Samsat),
   - memilih **Kategori Hadiah** dan **Hadiah**,
   - mengatur **jumlah pemenang per Samsat**,
   - menekan **Simpan**.
4. Setelah disimpan, halaman undian muncul dan tombol Start dapat ditekan.
5. Panel **Pengaturan Tampilan** menyediakan ukuran tombol start, jarak
   undian dari atas, skala tabel, **ukuran font tabel**, warna tabel, dan
   background.

## 5. Statistik Dashboard Admin

Widget di dashboard `/admin` menampilkan:
- **Total Kendaraan** → seluruh kendaraan Jawa Tengah.
- **Kecamatan** → jumlah kecamatan se-Jawa Tengah (dari `config/coordinator.php`).
- **Kelurahan** → jumlah kelurahan se-Jawa Tengah (dari `config/coordinator.php`).
