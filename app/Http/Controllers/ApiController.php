<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\Prize;
use App\Support\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function prizeAll()
    {
        return response()->json(Prize::with('prize_category')->withCount('winners')->get(), 200);
    }

    public function locationsAll()
    {
        $locations = Kendaraan::select('id_lokasi', 'lokasi')
            ->distinct()
            ->orderBy('lokasi')
            ->get();
        return response()->json($locations, 200);
    }

    /**
     * Mapping koordinator (Plat) -> label wilayah + daftar Samsat.
     *
     * Format respons mengikuti aplikasi undianbapenda:
     *
     *     {
     *       "Plat H": {
     *         "label": "Semarang Raya",
     *         "samsat": ["Semarang I", "Semarang II", ...]
     *       },
     *       ...
     *     }
     *
     * Samsat Pembantu tidak ditampilkan; hanya induknya, mis. "Blora".
     */
    public function coordinatorMapping()
    {
        return response()->json(Wilayah::coordinatorOptions(), 200);
    }

    /**
     * Informasi wilayah untuk halaman undian.
     *
     * Bila APP_KABKOTA kosong, data yang dikembalikan mencakup seluruh
     * Jawa Tengah (semua koordinator/Samsat).
     */
    public function wilayah()
    {
        $active = trim((string) config('wilayah.active', ''));
        $isProvince = $active === '';
        $winnersPerPage = (int) config('wilayah.winners_per_page', 5);
        $idLokasi = collect(config('wilayah.id_lokasi', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $info = $isProvince ? Wilayah::province() : Wilayah::find($active);

        // APP_KABKOTA yang tidak ditemukan tidak boleh menghentikan aplikasi;
        // jatuh kembali ke cakupan seluruh Jawa Tengah.
        if (!$info['found']) {
            $isProvince = true;
            $info = Wilayah::province();
        }

        // Validasi silang APP_ID_LOKASI hanya relevan saat dibatasi ke satu
        // Kabupaten/Kota.
        $idLokasiMismatch = [];
        $idLokasiTidakTerpakai = [];

        if (!$isProvince && !empty($idLokasi)) {
            $idLokasiMismatch = array_values(array_diff($info['samsat_ids'], $idLokasi));
            $idLokasiTidakTerpakai = array_values(array_diff($idLokasi, $info['samsat_ids']));
        }

        return response()->json([
            'scope'            => $isProvince ? 'province' : 'kabkota',
            'kabkota'          => $info['name'],
            'label'            => $isProvince ? config('wilayah.province_name', 'Provinsi Jawa Tengah') : $info['label'],
            'plat'             => $info['plat'],
            'samsat'           => array_keys($info['samsat']),
            'kecamatan'        => $info['kecamatan'],
            'kecamatan_samsat' => $info['kecamatan_samsat'],
            'samsat_ids'       => $info['samsat_ids'],
            'id_lokasi'        => $idLokasi,
            'id_lokasi_mismatch' => $idLokasiMismatch,
            'id_lokasi_tidak_terpakai' => $idLokasiTidakTerpakai,
            'total_kecamatan'  => $info['kecamatan_count'],
            'total_kelurahan'  => $info['kelurahan_count'],
            'winners_per_page' => $winnersPerPage,
            // Kode plat untuk animasi pengundian.
            'plat_kode'        => $isProvince
                ? ($info['plats'] ?? [])
                : self::extractPlateCode($info['plat']),
        ], 200);
    }

    /**
     * Stok data acakan untuk animasi pengundian Grand Prize.
     *
     * Mengambil sampel kendaraan yang BELUM pernah menang langsung dari
     * database sehingga tampilan pengacakan (nama, alamat, kecamatan, roda)
     * terasa nyata dan bervariatif — tetapi bukan data pemenang, jadi hasil
     * undian tidak bocor sebelum di-reveal.
     */
    public function scrambleStock()
    {
        $query = Kendaraan::query()
            ->select('id_kendaraan', 'nama', 'alamat', 'id_kecamatan', 'lokasi', 'roda')
            // Kendaraan yang sudah pernah menang tidak boleh ikut sampel.
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('winners as w')
                    ->whereColumn('w.id_kendaraan', 'kendaraans.id_kendaraan');
            });

        // Kendaraan yang masuk daftar grand prize juga dikeluarkan.
        // Perbandingan mengikuti SP getWinner: id_kendaraan dicast ke angka.
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $query->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('grand_prizes as g')
                    ->whereColumn('g.id', DB::raw('TRY_CAST(kendaraans.id_kendaraan AS BIGINT)'));
            });
        } else {
            $query->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('grand_prizes as g')
                    ->whereColumn('g.id', DB::raw('CAST(kendaraans.id_kendaraan AS SIGNED)'));
            });
        }

        return response()->json(
            $query->inRandomOrder()->limit(60)->get(),
            200,
        );
    }

    /**
     * Ambil kode plat dari label mapping wilayah (mis. "Plat AD" -> "AD").
     * Bila berisi beberapa kode dipisah spasi/koma, kembalikan semuanya.
     *
     * @return array<int, string>
     */
    public static function extractPlateCode(?string $platLabel): array
    {
        return \App\Support\Wilayah::plateCodesFromLabel($platLabel);
    }
}
