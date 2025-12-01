<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\PieceRate;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use App\Models\SewingReturnLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SewingPayrollGenerator
{
    public static function generate(
        $periodStart,
        $periodEnd,
        ?int $createdByUserId = null,
        ?PieceworkPayrollPeriod $existingPeriod = null,
    ): PieceworkPayrollPeriod {
        // Normalisasi tanggal ke Carbon
        $start = Carbon::parse($periodStart)->startOfDay();
        $end = Carbon::parse($periodEnd)->endOfDay();

        return DB::transaction(function () use ($start, $end, $createdByUserId, $existingPeriod) {
            // ==========================
            // 1. Siapkan / pakai period
            // ==========================
            if ($existingPeriod) {
                // MODE REGENERATE IN-PLACE
                /** @var PieceworkPayrollPeriod $period */
                $period = $existingPeriod->fresh();

                // Bersihkan semua line lama
                $period->lines()->delete();

                // Reset total
                $period->total_amount = 0;
                $period->save();
            } else {
                // MODE GENERATE BARU
                $period = PieceworkPayrollPeriod::create([
                    'module' => 'sewing',
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'status' => 'draft',
                    'created_by' => $createdByUserId,
                    'total_amount' => 0,
                ]);
            }

            // ==========================
            // 2. LOGIKA LAMA AGREGASI DATA
            // ==========================
            // ⬇️ PASTE di sini logika lama kamu yang:
            // - SELECT dari sewing_return_lines
            // - GROUP BY employee_id, item_category_id, item_id
            // - Cari rate dari PieceRate
            // - Insert PieceworkPayrollLine
            //
            // Contoh struktur KASAR (sesuaikan dengan versi kamu):

            $rows = SewingReturnLine::query()
                ->join('sewing_returns', 'sewing_return_lines.sewing_return_id', '=', 'sewing_returns.id')
                ->join('sewing_pickup_lines', 'sewing_return_lines.sewing_pickup_line_id', '=', 'sewing_pickup_lines.id')
                ->join('items', 'sewing_pickup_lines.finished_item_id', '=', 'items.id')
                ->whereDate('sewing_returns.date', '>=', $start->toDateString())
                ->whereDate('sewing_returns.date', '<=', $end->toDateString())
                ->where('sewing_return_lines.qty_ok', '>', 0)
                ->selectRaw('
        sewing_returns.operator_id as employee_id,
        items.item_category_id as item_category_id,
        sewing_pickup_lines.finished_item_id as item_id,
        SUM(sewing_return_lines.qty_ok) as total_qty_ok
    ')
                ->groupByRaw('
        employee_id,
        item_category_id,
        item_id
    ')
                ->get();

            $totalAmount = 0;

            foreach ($rows as $row) {
                // Cari rate (by item dulu, kalau nggak ada by category)
                $rate = PieceRate::query()
                    ->where('module', 'sewing')
                    ->where('employee_id', $row->employee_id)
                    ->where(function ($q) use ($row) {
                        $q->where('item_id', $row->item_id)
                            ->orWhere(function ($q2) use ($row) {
                                $q2->whereNull('item_id')
                                    ->where('item_category_id', $row->item_category_id);
                            });
                    })
                    ->activeAt($end) // kalau kamu punya scope ini
                    ->orderByDesc('item_id') // prioritas yang paling spesifik
                    ->first();

                $rateValue = $rate?->rate_per_pcs ?? 0;
                $amount = $rateValue * $row->total_qty_ok;
                $totalAmount += $amount;

                PieceworkPayrollLine::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $row->employee_id,
                    'item_category_id' => $row->item_category_id,
                    'item_id' => $row->item_id,
                    'total_qty_ok' => $row->total_qty_ok,
                    'rate_per_pcs' => $rateValue,
                    'amount' => $amount,
                ]);
            }

            // ==========================
            // 3. Update total & return
            // ==========================
            $period->update([
                'total_amount' => $totalAmount,
            ]);

            return $period->fresh(['lines']);
        });
    }

    /**
     * Ambil tarif borongan SEWING
     * - module = 'sewing'
     * - prioritas: item_id > item_category_id > default_piece_rate karyawan
     */
    public static function resolveRate(
        int $employeeId,
        ?int $itemCategoryId,
        ?int $itemId,
        string $atDate
    ): float {
        $query = PieceRate::query()
            ->where('module', 'sewing')
            ->where('employee_id', $employeeId)
            ->where('effective_from', '<=', $atDate)
            ->where(function ($q) use ($atDate) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $atDate);
            });

        $query->where(function ($q) use ($itemId, $itemCategoryId) {
            if ($itemId) {
                $q->where('item_id', $itemId);
            }

            if ($itemCategoryId) {
                $q->orWhere(function ($q2) use ($itemCategoryId) {
                    $q2->whereNull('item_id')
                        ->where('item_category_id', $itemCategoryId);
                });
            }
        });

        $pieceRate = $query
            ->orderByDesc('item_id')
            ->orderByDesc('item_category_id')
            ->first();

        if ($pieceRate) {
            return (float) $pieceRate->rate_per_pcs;
        }

        $employee = Employee::find($employeeId);
        if ($employee && $employee->default_piece_rate) {
            return (float) $employee->default_piece_rate;
        }

        return 0.0;
    }
}
