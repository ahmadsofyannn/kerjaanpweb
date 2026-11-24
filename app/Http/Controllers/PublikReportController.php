<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\ParkingTransaction;
use App\Models\Vehicle;
use App\Services\ParkingPredictionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublikReportController extends Controller
{
    public function __construct(
        private readonly ParkingPredictionService $predictionService
    ) {}

    /**
     * Daftar kendaraan beserta riwayat kunjungan dan status parkir saat ini.
     */
    public function kendaraan(Request $request): View
    {
        $search = strtoupper(preg_replace('/\s+/', ' ', trim((string) $request->query('q', ''))));

        $query = Vehicle::query()
            ->withCount('parkingTransactions')
            ->with([
                'parkingTransactions' => fn ($q) => $q
                    ->with('slot.block')
                    ->latest('entered_at')
                    ->limit(1),
            ])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('plate_number', 'like', "%{$search}%")
                        ->orWhere('owner_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('plate_number');

        $vehicles = $query->paginate(15)->withQueryString();

        $totalVehicles = Vehicle::query()->count();
        $currentlyParked = ParkingTransaction::query()->whereNull('exited_at')->count();

        return view('kendaraan.index', [
            'vehicles' => $vehicles,
            'search' => $search,
            'totalVehicles' => $totalVehicles,
            'currentlyParked' => $currentlyParked,
        ]);
    }

    /**
     * Ringkasan okupansi parkir per blok berdasarkan data slot & transaksi aktif.
     */
    public function okupansi(): View
    {
        $blocks = Block::query()
            ->with(['slots' => fn ($q) => $q->where('is_active', true)->withCount([
                'parkingTransactions as active_count' => fn ($tx) => $tx->whereNull('exited_at'),
            ])])
            ->orderBy('code')
            ->get();

        $summary = $blocks->map(function (Block $block): array {
            $totalSlots = $block->slots->count();
            $occupied = (int) $block->slots->sum('active_count');
            $available = max(0, $totalSlots - $occupied);
            $occupancyPct = $totalSlots > 0 ? round(($occupied / $totalSlots) * 100, 1) : 0.0;

            return [
                'code' => $block->code,
                'name' => $block->name,
                'total_slots' => $totalSlots,
                'occupied' => $occupied,
                'available' => $available,
                'occupancy_pct' => $occupancyPct,
            ];
        });

        return view('laporan.okupansi', [
            'blocks' => $summary,
            'totalSlots' => $summary->sum('total_slots'),
            'totalOccupied' => $summary->sum('occupied'),
            'totalAvailable' => $summary->sum('available'),
        ]);
    }

    /**
     * Halaman prediksi kepadatan & pemakaian slot parkir berbasis data historis.
     */
    public function prediksi(Request $request): View
    {
        $horizon = $request->query('horizon', 'hari');
        if (! in_array($horizon, ['hari', 'minggu', 'bulan'], true)) {
            $horizon = 'hari';
        }

        $prediction = $this->predictionService->generate($horizon);

        return view('prediksi.index', [
            'horizon' => $horizon,
            'prediction' => $prediction,
        ]);
    }
}