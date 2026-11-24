<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\Prize;
use App\Support\Wilayah;
use Illuminate\Http\Request;

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
     * Samsat Pembantu digabung dengan induknya, mis. "Blora → Cepu".
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
