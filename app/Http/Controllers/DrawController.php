<?php

namespace App\Http\Controllers;

use App\Models\Draw;
use App\Models\Kendaraan;
use App\Models\Prize;
use App\Models\Winner;
use App\Services\DrawingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DrawController extends Controller
{
    public function __construct(
        protected DrawingService $drawingService
    ) {}

    /**
     * Proses pengundian.
     *
     * - Undian biasa : per koordinator/wilayah (Samsat), 5 pemenang per Samsat
     *                  (jumlah dapat diatur dari sidebar).
     * - Grand Prize  : global semua kecamatan, jumlah pemenang fleksibel.
     */
    public function pick(Request $request)
    {
        $isGlobal = $request->boolean('is_global', false);
        $isDemo   = $request->boolean('is_demo', false);

        if ($isGlobal) {
            $request->validate([
                'prize_ids'   => 'required|array|min:1',
                'prize_ids.*' => 'required|numeric|exists:prizes,id',
                'quantity'    => 'required|integer|min:1',
            ]);
        } else {
            $request->validate([
                'prize_ids'   => 'required|array|min:1',
                'prize_ids.*' => 'required|numeric|exists:prizes,id',
                'samsats'     => 'required|array|min:1',
                'samsats.*'   => 'required|string',
                'per_samsat'  => 'nullable|integer|min:1',
            ]);
        }

        $prizeIds = $request->prize_ids;

        // 1. Validasi prize dan ketersediaan
        $prizes      = Prize::whereIn('id', $prizeIds)->get();
        $categoryIds = $prizes->pluck('prize_category_id')->unique();

        if ($categoryIds->count() > 1) {
            return response()->json([
                'message' => 'Semua hadiah harus berasal dari satu kategori yang sama.'
            ], 422);
        }

        $drawCategoryId = $categoryIds->first();
        $isBbnkb        = $prizes->first()?->prize_category?->isBbnkb() ?? false;
        $totalAvailable = $prizes->sum(fn($p) => $p->available_quantity);

        if ($isGlobal) {
            return $this->pickGlobal(
                $request,
                $prizeIds,
                $prizes,
                $drawCategoryId,
                $totalAvailable,
                $isDemo,
                $isBbnkb
            );
        }

        $perSamsat = (int) $request->input('per_samsat', 5);

        return $this->pickBySamsat(
            $prizeIds,
            $request->samsats,
            $totalAvailable,
            $isDemo,
            $perSamsat,
            $isBbnkb
        );
    }

    /**
     * Grand Prize: undian global dari semua kecamatan.
     *
     * Jumlah pemenang bebas sesuai kebutuhan acara (fleksibel), tanpa
     * komposisi roda. Kandidat tetap diambil melalui SP getWinner mode
     * global (id_lokasi = 999) yang sudah ada.
     */
    protected function pickGlobal(
        Request $request,
        array $prizeIds,
        $prizes,
        $drawCategoryId,
        $totalAvailable,
        bool $isDemo,
        bool $isBbnkb = false
    ) {
        $required = (int) $request->quantity;

        if ($totalAvailable < $required) {
            return response()->json([
                'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$required})."
            ], 422);
        }

        $spWinners = $this->drawingService->getWinnerCandidates(999, '', $required, 10, $isBbnkb);

        if ($spWinners->count() < $required) {
            return response()->json([
                'message' => "Tidak cukup kendaraan tersedia. Tersedia: {$spWinners->count()}, dibutuhkan: {$required}."
            ], 422);
        }

        $selectedIds = $spWinners
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($selectedIds->count() < $required) {
            return response()->json([
                'message' => "Gagal memilih kendaraan yang cukup. Coba lagi."
            ], 422);
        }

        $vehicles = Kendaraan::whereIn('id', $selectedIds)->get()->keyBy('id');

        if ($vehicles->count() < $required) {
            return response()->json([
                'message' => "Gagal memilih kendaraan yang cukup. Coba lagi."
            ], 422);
        }

        $prizePool = $this->drawingService->buildPrizePool($prizes, $required);

        $draw = null;
        if (!$isDemo) {
            $draw = Draw::create([
                'prize_id'          => $prizeIds[0],
                'prize_category_id' => $drawCategoryId,
                'quantity'          => $required,
                // Penanda grand prize (global).
                'is_global'         => true,
            ]);
        }

        $results = [];
        foreach ($selectedIds as $index => $id) {
            $prizeId = $prizePool[$index];
            $vehicle = $vehicles[$id];

            if (!$isDemo) {
                Winner::create([
                    'draw_id'      => $draw->id,
                    'kendaraan_id' => $id,
                    'id_kendaraan' => $vehicle->id_kendaraan,
                    'prize_id'     => $prizeId,
                ]);
            }

            $vehicle->prize = $prizes->firstWhere('id', $prizeId);
            $results[]      = $vehicle;
        }

        $response = ['data' => $results];
        if ($draw) {
            $response['draw_id'] = $draw->id;
        }

        return response()->json($response);
    }

    protected function pickBySamsat(
        array $prizeIds,
        array $samsats,
        $totalAvailable,
        bool $isDemo,
        int $perSamsat = 5,
        bool $isBbnkb = false
    ) {
        $required = count($samsats) * $perSamsat;

        if ($totalAvailable < $required) {
            return response()->json([
                'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$required}). Pilih lebih banyak hadiah atau kurangi jumlah Samsat."
            ], 422);
        }

        $response = DB::transaction(function () use ($prizeIds, $samsats, $perSamsat, $isDemo, $isBbnkb) {
            return $this->drawingService->pickDraw($prizeIds, $samsats, $perSamsat, $isDemo, $isBbnkb);
        });

        if (isset($response['status']) && $response['status'] === 422) {
            return response()->json([
                'message' => $response['message'],
            ], 422);
        }

        return response()->json($response);
    }

    /**
     * Hasil undian (non-demo) yang baru saja tersimpan.
     *
     * Dipakai untuk memulihkan tampilan di front-end bila koneksi terputus
     * (mis. perangkat standby lama): request pengundian mungkin sudah
     * selesai dan tersimpan di server, tetapi respons tidak sempat
     * diterima front-end. Pemenang dikembalikan dalam bentuk yang sama
     * dengan respons endpoint pick.
     */
    public function recent()
    {
        $draw = Draw::query()
            ->where('created_at', '>=', now()->subMinutes(3))
            ->latest('id')
            ->first();

        if (!$draw) {
            return response()->json([
                'data'      => [],
                'recovered' => false,
            ]);
        }

        $winners = $draw->winners()->with(['kendaraan', 'prize.prize_category'])->get();

        $data = $winners
            ->map(function (Winner $winner) {
                $vehicle = $winner->kendaraan;

                if (!$vehicle) {
                    return null;
                }

                $vehicle->prize = $winner->prize;

                // Pertahankan lokasi Samsat/wilayah untuk pengelompokan
                // undian biasa; bila kosong, pakai id kecamatan.
                $vehicle->lokasi = $vehicle->lokasi ?: ($vehicle->id_kecamatan ?? '');

                return $vehicle;
            })
            ->filter()
            ->values();

        return response()->json([
            'data'      => $data,
            'draw_id'   => $draw->id,
            'recovered' => $data->isNotEmpty(),
        ]);
    }
}
