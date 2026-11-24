<?php

namespace App\Support;

/**
 * Helper untuk membaca mapping wilayah dari config/coordinator.php.
 *
 * Struktur asal: Plat -> Samsat -> Kota/Kabupaten -> Kecamatan -> Kelurahan.
 * Helper ini menggabungkan data per Kabupaten/Kota (case-insensitive).
 *
 * Catatan penting:
 * - Samsat Pembantu (punya 'induk') sering menduplikasi kecamatan/kelurahan
 *   dari Samsat induknya dengan ID berbeda. Maka penghitungan dilakukan dengan
 *   deduplikasi berdasarkan NAMA:
 *     - Kecamatan : nama kecamatan
 *     - Kelurahan : nama kecamatan + nama kelurahan
 * - 'id_lokasi' setiap kecamatan diambil dari Samsat pertama yang memuatnya
 *   (diutamakan Samsat induk, karena urutan mapping menempatkan induk lebih dulu).
 */
class Wilayah
{
    /**
     * Seluruh mapping dari config/coordinator.php.
     */
    public static function mapping(): array
    {
        return config('coordinator', []);
    }

    /**
     * Daftar unik Kabupaten/Kota (terurut abjad).
     *
     * @return array<int, string>
     */
    public static function kabkotaList(): array
    {
        $list = [];

        foreach (static::mapping() as $info) {
            foreach (($info['samsat'] ?? []) as $samsat) {
                $kota = $samsat['kota'] ?? null;

                if ($kota && !in_array($kota, $list, true)) {
                    $list[] = $kota;
                }
            }
        }

        sort($list);

        return $list;
    }

    /**
     * Cari data lengkap sebuah Kabupaten/Kota (case-insensitive).
     *
     * @return array{
     *     name: string,
     *     found: bool,
     *     plat: ?string,
     *     label: ?string,
     *     samsat: array,
     *     samsat_ids: array<int, int>,
     *     kecamatan: array<int, array{id: string, nama: string}>,
     *     kecamatan_samsat: array<string, int>,
     *     kecamatan_count: int,
     *     kelurahan_count: int
     * }
     */
    public static function find(?string $kabkota): array
    {
        $needle = trim((string) $kabkota);

        $result = [
            'name'             => $needle,
            'found'            => false,
            'plat'             => null,
            'label'            => null,
            'samsat'           => [],
            'samsat_ids'       => [],
            'kecamatan'        => [],
            'kecamatan_samsat' => [],
            'kecamatan_count'  => 0,
            'kelurahan_count'  => 0,
        ];

        if ($needle === '') {
            return $result;
        }

        $kecamatanByName = []; // nama (normal) => ['id' =>, 'nama' =>, 'samsat_id' =>]
        $kelurahanSeen = [];   // "kecamatan|kelurahan" (normal) => true

        foreach (static::mapping() as $plat => $info) {
            foreach (($info['samsat'] ?? []) as $samsatName => $samsat) {
                if (strcasecmp((string) ($samsat['kota'] ?? ''), $needle) !== 0) {
                    continue;
                }

                $result['found'] = true;
                $result['plat'] = $plat;
                $result['label'] = $info['label'] ?? null;
                $result['samsat'][$samsatName] = $samsat;

                $idLokasi = isset($samsat['id_lokasi']) ? (int) $samsat['id_lokasi'] : null;

                if ($idLokasi !== null && !in_array($idLokasi, $result['samsat_ids'], true)) {
                    $result['samsat_ids'][] = $idLokasi;
                }

                foreach (($samsat['kecamatan'] ?? []) as $kecId => $kec) {
                    $kecId = (string) $kecId;
                    $kecName = $kec['nama'] ?? $kecId;
                    $kecNorm = static::normalize($kecName);

                    // Kecamatan: dedupe berdasarkan nama (Samsat Pembantu bisa
                    // mengulang kecamatan yang sama dengan ID berbeda).
                    if (!isset($kecamatanByName[$kecNorm])) {
                        $kecamatanByName[$kecNorm] = [
                            'id'        => $kecId,
                            'nama'      => $kecName,
                            'samsat_id' => $idLokasi,
                        ];

                        if ($idLokasi !== null) {
                            $result['kecamatan_samsat'][$kecId] = $idLokasi;
                        }
                    }

                    foreach (($kec['kelurahan'] ?? []) as $kelId => $kelName) {
                        $key = $kecNorm . '|' . static::normalize($kelName);
                        $kelurahanSeen[$key] = true;
                    }
                }
            }
        }

        // Susun daftar kecamatan terurut berdasarkan id.
        $kecamatan = array_values($kecamatanByName);
        usort($kecamatan, fn ($a, $b) => strcmp((string) $a['id'], (string) $b['id']));

        $result['kecamatan'] = $kecamatan;
        $result['kecamatan_count'] = count($kecamatan);
        $result['kelurahan_count'] = count($kelurahanSeen);

        return $result;
    }

    /**
     * Daftar koordinator lokasi dengan format seperti aplikasi undianbapenda:
     * label wilayah + daftar Samsat (Samsat Pembantu digabung ke induknya
     * dengan format "Induk → Pembantu").
     *
     * @return array<string, array{label: string, samsat: array<int, string>}>
     */
    public static function coordinatorOptions(): array
    {
        $options = [];

        foreach (static::mapping() as $plat => $info) {
            $parents = [];
            $children = [];

            foreach (($info['samsat'] ?? []) as $samsatName => $samsat) {
                $displayName = static::samsatDisplayName($samsatName);
                $induk = $samsat['induk'] ?? null;

                if (!empty($induk)) {
                    $children[$induk][] = $displayName;
                } else {
                    $parents[$samsatName] = $displayName;
                }
            }

            $samsats = [];

            foreach ($parents as $samsatName => $displayName) {
                $subSamsats = $children[$samsatName] ?? [];

                $samsats[] = empty($subSamsats)
                    ? $displayName
                    : $displayName . ' → ' . implode(', ', $subSamsats);

                unset($children[$samsatName]);
            }

            // Samsat Pembantu yang induknya tidak ada di koordinator ini
            // tetap ditampilkan agar tidak hilang dari pilihan.
            foreach ($children as $subSamsats) {
                foreach ($subSamsats as $subSamsat) {
                    $samsats[] = $subSamsat;
                }
            }

            $options[$plat] = [
                'label'  => $info['label'] ?? $plat,
                'samsat' => $samsats,
            ];
        }

        return $options;
    }

    /**
     * Nama Samsat untuk tampilan (mis. "SEMARANG I" -> "Semarang I").
     * Angka romawi tetap huruf besar (I, II, III).
     */
    protected static function samsatDisplayName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $display = [];

        foreach ($parts as $part) {
            $upper = strtoupper($part);

            if (preg_match('/^[IVXLCDM]+$/', $upper)) {
                $display[] = $upper;
            } else {
                $display[] = mb_convert_case(mb_strtolower($part, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
            }
        }

        return implode(' ', $display);
    }

    /**
     * Data agregat seluruh Jawa Tengah (semua koordinator/Samsat).
     *
     * Dipakai bila aplikasi tidak dibatasi ke satu Kabupaten/Kota
     * (APP_KABKOTA kosong).
     *
     * @return array{
     *     name: string,
     *     found: bool,
     *     plat: null,
     *     label: string,
     *     samsat: array,
     *     samsat_ids: array<int, int>,
     *     kecamatan: array<int, array{id: string, nama: string, samsat_id: ?int, samsat: string}>,
     *     kecamatan_samsat: array<string, int>,
     *     kecamatan_count: int,
     *     kelurahan_count: int,
     *     plats: array<int, string>
     * }
     */
    public static function province(): array
    {
        $result = [
            'name'             => 'Jawa Tengah',
            'found'            => true,
            'plat'             => null,
            'label'            => 'Provinsi Jawa Tengah',
            'samsat'           => [],
            'samsat_ids'       => [],
            'kecamatan'        => [],
            'kecamatan_samsat' => [],
            'kecamatan_count'  => 0,
            'kelurahan_count'  => 0,
            'plats'            => [],
        ];

        $kecamatanByName = [];
        $kelurahanSeen = [];

        foreach (static::mapping() as $plat => $info) {
            $result['plats'] = array_merge($result['plats'], static::plateCodesFromLabel($plat));

            foreach (($info['samsat'] ?? []) as $samsatName => $samsat) {
                $result['samsat'][$samsatName] = $samsat;

                $idLokasi = isset($samsat['id_lokasi']) ? (int) $samsat['id_lokasi'] : null;

                if ($idLokasi !== null && !in_array($idLokasi, $result['samsat_ids'], true)) {
                    $result['samsat_ids'][] = $idLokasi;
                }

                foreach (($samsat['kecamatan'] ?? []) as $kecId => $kec) {
                    $kecId = (string) $kecId;
                    $kecName = $kec['nama'] ?? $kecId;
                    $kecNorm = static::normalize($kecName);

                    // Dedupe berdasarkan nama: Samsat Pembantu dapat
                    // mengulang kecamatan yang sama dengan ID berbeda.
                    if (!isset($kecamatanByName[$kecNorm])) {
                        $kecamatanByName[$kecNorm] = [
                            'id'        => $kecId,
                            'nama'      => $kecName,
                            'samsat_id' => $idLokasi,
                            'samsat'    => $samsatName,
                        ];

                        if ($idLokasi !== null) {
                            $result['kecamatan_samsat'][$kecId] = $idLokasi;
                        }
                    }

                    foreach (($kec['kelurahan'] ?? []) as $kelName) {
                        $key = $kecNorm . '|' . static::normalize($kelName);
                        $kelurahanSeen[$key] = true;
                    }
                }
            }
        }

        $kecamatan = array_values($kecamatanByName);
        usort($kecamatan, fn ($a, $b) => strcmp((string) $a['id'], (string) $b['id']));

        $result['kecamatan'] = $kecamatan;
        $result['kecamatan_count'] = count($kecamatan);
        $result['kelurahan_count'] = count($kelurahanSeen);
        $result['plats'] = array_values(array_unique($result['plats']));

        sort($result['samsat_ids']);

        return $result;
    }

    /**
     * Kode plat daerah aktif (mis. "Plat AD" -> ["AD"]) untuk animasi
     * pengundian. Sumber: mapping wilayah berdasarkan Kabupaten/Kota aktif
     * (.env APP_KABKOTA).
     *
     * Bila APP_KABKOTA kosong (mode seluruh Jawa Tengah), semua kode plat
     * dikembalikan.
     *
     * @return array<int, string>
     */
    public static function plateCodes(?string $kabkota = null): array
    {
        $kabkota = $kabkota ?? config('wilayah.active');

        if (trim((string) $kabkota) === '') {
            return static::province()['plats'];
        }

        $info = static::find($kabkota);

        return static::plateCodesFromLabel($info['plat'] ?? null);
    }

    /**
     * Ambil kode plat dari label mapping wilayah (mis. "Plat AD" -> ["AD"]).
     *
     * @return array<int, string>
     */
    public static function plateCodesFromLabel(?string $platLabel): array
    {
        $codes = preg_split('/[\s,\/]+/', strtoupper(trim((string) $platLabel))) ?: [];

        return array_values(array_filter(
            array_map(fn ($code) => preg_replace('/[^A-Z0-9]/', '', (string) $code), $codes),
            fn ($code) => $code !== '' && $code !== 'PLAT'
        ));
    }

    /**
     * Normalisasi teks untuk keperluan deduplikasi.
     */
    protected static function normalize(?string $value): string
    {
        $value = strtoupper(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }
}
