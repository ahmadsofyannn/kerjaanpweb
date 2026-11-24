<?php

namespace App\Services;

use App\Models\ParkingTransaction;
use App\Models\Slot;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ParkingPredictionService
{
    /**
     * Hasilkan prediksi kepadatan parkir berdasarkan data historis transaksi.
     *
     * @return array<string, mixed>
     */
    public function generate(string $horizon = 'hari'): array
    {
        $now = Carbon::now();
        $lookbackStart = $now->copy()->subWeeks(8)->startOfDay();

        $activeSlots = Slot::query()
            ->with('block')
            ->where('is_active', true)
            ->orderBy('block_id')
            ->orderBy('number')
            ->get();

        $activeSlotCount = max(1, $activeSlots->count());

        $transactions = ParkingTransaction::query()
            ->whereNotNull('entered_at')
            ->where('entered_at', '>=', $lookbackStart)
            ->get(['slot_id', 'entered_at']);

        $hasHistory = $transactions->isNotEmpty();

        // Kelompokkan transaksi per tanggal (Y-m-d), hari dalam minggu (0=Minggu..6=Sabtu), jam (0..23), dan slot_id
        $dailyCounts = [];
        $dayOfWeekTotals = array_fill(0, 7, 0);
        $dayOfWeekOccurrences = [];
        $hourTotals = array_fill(0, 24, 0);
        $slotTotals = [];

        foreach ($transactions as $tx) {
            $entered = $tx->entered_at instanceof CarbonInterface
                ? $tx->entered_at->copy()
                : Carbon::parse($tx->entered_at);

            $dateKey = $entered->format('Y-m-d');
            $dow = (int) $entered->dayOfWeek;
            $hour = (int) $entered->hour;

            $dailyCounts[$dateKey] = ($dailyCounts[$dateKey] ?? 0) + 1;
            $dayOfWeekTotals[$dow] = ($dayOfWeekTotals[$dow] ?? 0) + 1;
            $dayOfWeekOccurrences[$dow][$dateKey] = true;
            $hourTotals[$hour] = ($hourTotals[$hour] ?? 0) + 1;

            if ($tx->slot_id !== null) {
                $slotTotals[$tx->slot_id] = ($slotTotals[$tx->slot_id] ?? 0) + 1;
            }
        }

        // Hitung moving average harian (7 hari terakhir)
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i)->format('Y-m-d');
            $last7Days[] = (int) ($dailyCounts[$d] ?? 0);
        }

        $movingAvg7Days = round(array_sum($last7Days) / 7, 1);
        $movingAvg3Days = round(array_sum(array_slice($last7Days, -3)) / 3, 1);

        // Rata-rata transaksi pada hari yang sama dengan target prediksi (besok untuk 'hari', 7 hari ke depan untuk 'minggu', 30 hari untuk 'bulan')
        $targetDay = $now->copy()->addDay();
        $targetDow = (int) $targetDay->dayOfWeek;
        $sameDowDatesCount = count($dayOfWeekOccurrences[$targetDow] ?? []);

        $avgSameDow = $sameDowDatesCount > 0
            ? round($dayOfWeekTotals[$targetDow] / $sameDowDatesCount, 1)
            : $movingAvg7Days;

        // Gabungkan tren moving average + pola hari yang sama
        $predictedDailyEntries = (int) round(($avgSameDow * 0.6) + ($movingAvg7Days * 0.4));

        $multiplier = match ($horizon) {
            'minggu' => 7,
            'bulan' => 30,
            default => 1,
        };

        $predictedTotalEntries = $predictedDailyEntries * $multiplier;
        $estimatedOccupancyPct = min(
            100.0,
            round(($predictedDailyEntries / $activeSlotCount) * 100, 1)
        );

        // Prediksi per jam (06:00 - 21:00) berdasarkan rata-rata historis per jam dalam 8 minggu
        $weeksLookback = 8;
        $hourlyForecast = [];
        for ($hour = 6; $hour <= 21; $hour++) {
            $avgPerHour = round(($hourTotals[$hour] ?? 0) / $weeksLookback, 1);
            $occPct = min(100.0, round(($avgPerHour / $activeSlotCount) * 100, 1));

            $hourlyForecast[] = [
                'hour_label' => sprintf('%02d:00 - %02d:00', $hour, $hour + 1),
                'avg_entries' => $avgPerHour,
                'occupancy_pct' => $occPct,
                'level' => $this->classifyLevel($occPct),
            ];
        }

        // Prediksi prioritas pemakaian per slot / blok berdasarkan proporsi historis
        $totalSlotTx = max(1, array_sum($slotTotals));
        $slotForecast = $activeSlots->map(function (Slot $slot) use ($slotTotals, $totalSlotTx, $predictedTotalEntries, $activeSlotCount): array {
            $rawTx = (int) ($slotTotals[$slot->id] ?? 0);
            $weight = array_sum($slotTotals) > 0
                ? ($rawTx / $totalSlotTx)
                : (1 / $activeSlotCount);

            $estEntries = (int) round($predictedTotalEntries * $weight);
            $sharePct = round($weight * 100, 1);

            return [
                'label' => ($slot->block?->code ?? '-') . '-' . $slot->number,
                'block_name' => $slot->block?->name ?? 'Tanpa Blok',
                'historical_count' => $rawTx,
                'predicted_entries' => $estEntries,
                'share_pct' => $sharePct,
                'level' => $this->classifyLevel($sharePct * 3),
            ];
        })->sortByDesc('predicted_entries')->values()->take(10);

        return [
            'has_history' => $hasHistory,
            'horizon' => $horizon,
            'horizon_label' => match ($horizon) {
                'minggu' => '7 Hari Ke Depan',
                'bulan' => '30 Hari Ke Depan',
                default => 'Hari Berikutnya (' . $targetDay->translatedFormat('l, d M Y') . ')',
            },
            'active_slots' => $activeSlots->count(),
            'moving_avg_3d' => $movingAvg3Days,
            'moving_avg_7d' => $movingAvg7Days,
            'predicted_daily_entries' => $predictedDailyEntries,
            'predicted_total_entries' => $predictedTotalEntries,
            'estimated_occupancy_pct' => $estimatedOccupancyPct,
            'level' => $this->classifyLevel($estimatedOccupancyPct),
            'hourly_forecast' => $hourlyForecast,
            'slot_forecast' => $slotForecast,
        ];
    }

    private function classifyLevel(float $score): array
    {
        if ($score >= 70) {
            return ['label' => 'Padat', 'badge' => 'text-bg-danger'];
        }

        if ($score >= 35) {
            return ['label' => 'Sedang', 'badge' => 'text-bg-warning'];
        }

        return ['label' => 'Lengang', 'badge' => 'text-bg-success'];
    }
}