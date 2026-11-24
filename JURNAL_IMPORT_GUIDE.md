# Impor Data Jurnal Kendaraan (CSV)

Panduan ini menjelaskan impor data kendaraan dari file
`jurnal_laporan.csv` (ekspor Bapenda) ke tabel `kendaraans` pada aplikasi
undian ini.

## 1. Ringkasan Hasil Impor

| Keterangan | Nilai |
|---|---|
| Baris CSV dibaca | 1.048.575 |
| Kendaraan baru dari CSV | 919.045 |
| Data lama yang dipertahankan | 220 |
| Kendaraan dummy pelengkap Samsat | 500 (Bandongan) |
| Total kendaraan setelah impor | 919.765 |
| **Total setelah trim 100/kelurahan** | **560.728** |
| Kendaraan pemerintah setelah trim | 0 |
| `id_kendaraan` duplikat | 0 |
| Samsat pada `config/uppd.php` yang punya data | 48 dari 48 |

Sumber CSV memiliki 42 kolom dan memuat data kendaraan dari seluruh
Samsat di Jawa Tengah. Baris tanpa `id_kendaraan`, lokasi yang tidak
dikenal, dan `id_kendaraan` duplikat dilewati/dideduplikasi.

## 2. Perintah Impor

Prasyarat:

- SQL Server aktif dan database pada `.env` dapat diakses.
- Ekstensi PHP `pdo_sqlsrv` aktif.
- Utilitas `bcp` tersedia di `PATH` (biasanya dari SQL Server Client Tools).

Jalankan:

```powershell
php artisan kendaraan:import-jurnal "C:\Users\USER A\Downloads\atad\jurnal_laporan.csv"
```

Uji cepat dengan sebagian data:

```powershell
php artisan kendaraan:import-jurnal "C:\...\jurnal_laporan.csv" --limit=5000 --no-fill-missing --skip-index
```

Opsi yang tersedia:

| Opsi | Keterangan |
|---|---|
| `--limit=N` | Hanya membaca N baris data (header tidak dihitung). |
| `--no-fill-missing` | Tidak membuat kendaraan dummy untuk Samsat yang belum punya data. |
| `--skip-index` | Tidak membuat index/statistik setelah impor. |
| `--keep-staging` | Menyimpan tabel staging untuk pemeriksaan. |

Impor bersifat **idempoten**: kendaraan dengan `id_kendaraan` yang sudah
ada tidak dimasukkan lagi. Mengulang perintah aman.

> Jika database di-reset (`db:wipe` + `migrate --seed`), jalankan kembali
> command impor ini karena file CSV tidak disimpan di dalam repository.

## 3. Pemetaan Kolom

| Kolom CSV | Kolom `kendaraans` | Catatan |
|---|---|---|
| `id_kendaraan` | `id_kendaraan` | Kunci deduplikasi. Baris kosong dilewati. |
| `no_polisi` | `no_polisi` | Diubah ke huruf besar. |
| `nama_pemilik` | `nama` | Kosong menjadi `-`. |
| `alamat` | `alamat` | Kosong menjadi `-`. |
| `lokasi_samsat` | `id_lokasi`, `lokasi`, `id_lokasi_proses`, `lokasi_proses` | Dicocokkan ke `config/uppd.php`. |
| `id_kelurahan` | `id_kecamatan`, `id_kelurahan` | Kode 7 digit dinormalisasi (leading zero dipulihkan), lalu divalidasi ke `config/coordinator.php`. Fallback ke nama kecamatan. |
| `jml_roda` | `roda` | Nilai tidak wajar dinormalisasi ke 2/4 (nilai 6/8/10 dipertahankan). |
| `warna_tnkb` | `warna_tnkb`, `id_warna_tnkb` | Fallback berdasarkan `warna_kend` bila kosong/tidak dikenal. |
| `fungsi_kend` | `fungsi_kend`, `id_fungsi_kend` | Pribadi/Umum/Dinas/Yayasan. |

Kolom berikut tidak tersedia di CSV sehingga dibuat deterministik agar
memenuhi kolom `NOT NULL` dan tetap realistis:

| Kolom | Aturan |
|---|---|
| `id_billing` | `9825` + 12 digit terakhir `id_kendaraan` (16 digit). |
| `id_pendaftaran`, `pendaftaran` | Distribusi 5% BBNKB, 20% Mutasi, 65% P.U./Pembaharuan, 10% Ganti STNK. |
| `tgl_daftar`, `tgl_bayar` | 1 Desember 2025 s.d. 30 Mei 2026, ditentukan dari hash `id_kendaraan`. |
| `tgl_jatuh_tempo` | `tgl_daftar` + 1 tahun. |
| `tgl_akhir_stnk` | `tgl_daftar` + 3 tahun. |

Catatan kualitas data:

- Sebagian kecil baris CSV memiliki tanda `;` di dalam alamat sehingga
  kolom setelah alamat bergeser. `no_polisi`, `nama`, `lokasi`, dan `roda`
  tetap benar; sebagian `id_kecamatan`/`id_kelurahan` pada baris tersebut
  bisa kosong.
- Kode kelurahan placeholder (`...9999` / `LAIN-LAIN`) tidak dipakai.
  Pengundian tetap berjalan karena filter utama adalah Samsat
  (`id_lokasi`), bukan kelurahan.

## 4. Perbaikan Agar Pengundian Andal

### 4.1 Stored procedure pengundian

Migrasi `2026_11_23_000000_recreate_get_winner_procedures` membuat ulang:

- `getWinner(@id_lokasi_samsat, @id_kecamatan, @length)`
- `getWinnerBbn(@id_lokasi_samsat, @id_kecamatan, @length)`

Versi baru memfilter kendaraan berdasarkan Samsat/kecamatan **terlebih
dahulu**, lalu mengacak dengan `ORDER BY NEWID()`. Versi lama mengambil
sampel ID acak dari seluruh tabel; untuk Samsat kecil cara itu bisa
mengembalikan lebih sedikit dari jumlah yang diminta.

`getWinnerBbn` hanya memakai kendaraan dengan `id_pendaftaran = 1`
(BBNKB), dan sekarang ikut dibuat oleh migrasi sehingga tidak hilang saat
database dibangun ulang.

### 4.2 Index

Setelah impor, command membuat index:

- `IX_kendaraans_id_kendaraan`
- `IX_kendaraans_lokasi_kecamatan`
- `IX_kendaraans_id_pendaftaran`
- `IX_winners_id_kendaraan`
- `IX_grand_prizes_id`

Index ini mempercepat filter pemenang lama, grand prize, dan pengundian
per Samsat.

### 4.3 Samsat tanpa data

CSV tidak memuat data Samsat **Bandongan** (id_lokasi 52), padahal Samsat
tersebut ada di pilihan aplikasi. Command otomatis membuat 500 kendaraan
dummy untuk Samsat dengan data kurang dari 100 kendaraan. Dengan begitu,
memilih seluruh Samsat tidak menghasilkan error "Tidak cukup kendaraan
tersedia".

## 5. Verifikasi

Uji seluruh alur pengundian (semua Samsat, grand prize, BBNKB, gabungan,
pengundian demo, penyimpanan pemenang, dan endpoint HTTP):

```powershell
php scripts/verify-drawing.php
```

Contoh uji manual SQL:

```sql
EXEC getWinner 29, '', 5;      -- 5 pemenang Samsat Cilacap
EXEC getWinner 52, '', 5;      -- 5 pemenang Samsat Bandongan
EXEC getWinner 999, '', 10;    -- 10 pemenang Grand Prize
EXEC getWinnerBbn 29, '', 5;   -- 5 pemenang kategori BBNKB
```

Cek jumlah data:

```sql
SELECT COUNT(*) FROM kendaraans;
SELECT id_lokasi, lokasi, COUNT(*) FROM kendaraans GROUP BY id_lokasi, lokasi ORDER BY id_lokasi;
```

## 6. Merampingkan Data per Kelurahan

Setelah impor besar, database dapat dirapikan dengan menyisakan maksimal
**100 kendaraan non-pemerintah per kelurahan**:

```powershell
php artisan kendaraan:trim-per-kelurahan --dry-run
php artisan kendaraan:trim-per-kelurahan --force
```

Aturan yang dipakai:

- Kendaraan pemerintah dihapus. Kendaraan dikenali sebagai pemerintah jika
  `fungsi_kend = 'Dinas'` atau warna TNKB mengandung `MERAH`.
- Maksimal 100 kendaraan non-pemerintah disimpan per `id_kelurahan`.
- Baris tanpa `id_kelurahan` dihapus karena tidak termasuk kelurahan.
  Gunakan `--keep-null-kelurahan` bila ingin menyisakan maksimal 100 per
  kecamatan untuk baris tersebut.
- Kendaraan yang sudah menjadi pemenang selalu dipertahankan agar riwayat
  undian tidak rusak.
- `--limit=N` dapat dipakai untuk mengubah batas 100.
- `--dry-run` hanya menampilkan perkiraan jumlah yang akan dihapus.

Contoh hasil pada database ini: 919.765 kendaraan menjadi **560.728**
kendaraan; 8.691 kelurahan, maksimal 100 kendaraan per kelurahan, tanpa
kendaraan pemerintah dan tanpa baris tanpa kode kelurahan.

> Buat backup database sebelum menjalankan trim. Contoh:
>
> ```sql
> BACKUP DATABASE undian_samsat
> TO DISK = N'C:\Program Files\Microsoft SQL Server\MSSQL17.MSSQLSERVER\MSSQL\Backup\undian_samsat_sebelum_trim.bak'
> WITH COMPRESSION, INIT;
> ```
>
> Data yang sudah dihapus hanya bisa dikembalikan dari backup.

## 7. File Terkait

- `app/Console/Commands/ImportJurnalKendaraan.php` — command impor.
- `app/Console/Commands/TrimKendaraanPerKelurahan.php` — command trim per kelurahan.
- `app/Support/GetWinnerProcedure.php` — definisi SP `getWinner` dan `getWinnerBbn`.
- `database/migrations/2026_11_23_000000_recreate_get_winner_procedures.php` — migrasi pembaruan SP.
- `scripts/verify-drawing.php` — pengujian alur undian.
- `config/uppd.php` — mapping Samsat ke `id_lokasi`.
- `config/coordinator.php` — mapping kecamatan/kelurahan.

> Data CSV berisi nama dan alamat pemilik kendaraan. Gunakan hanya untuk
> lingkungan pengembangan/lokal dan jangan dipublikasikan tanpa izin.
