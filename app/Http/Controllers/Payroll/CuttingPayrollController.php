<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use App\Services\Payroll\CuttingPayrollGenerator;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CuttingPayrollController extends Controller
{
    /**
     * Tampilkan daftar periode payroll cutting
     */
    public function index(Request $request): View
    {
        $query = PieceworkPayrollPeriod::query()
            ->where('module', 'cutting')
            ->orderByDesc('period_start');

        // Optional filter tanggal dari request
        if ($request->filled('from')) {
            $query->whereDate('period_start', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('period_end', '<=', $request->input('to'));
        }

        $periods = $query->paginate(15)->withQueryString();

        return view('payroll.cutting.index', [
            'periods' => $periods,
        ]);
    }

    /**
     * Form pilih periode untuk generate payroll cutting
     */
    public function create(Request $request): View
    {
        // Default periode: 1 minggu terakhir
        $defaultEnd = Carbon::today();
        $defaultStart = (clone $defaultEnd)->subDays(6);

        return view('payroll.cutting.create', [
            'defaultStart' => $defaultStart->toDateString(),
            'defaultEnd' => $defaultEnd->toDateString(),
        ]);
    }

    /**
     * Proses generate payroll cutting untuk periode tertentu
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ], [
            'period_start.required' => 'Tanggal awal periode wajib diisi.',
            'period_end.required' => 'Tanggal akhir periode wajib diisi.',
            'period_end.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
        ]);

        $userId = Auth::id();

        // CARI PERIODE YANG SUDAH ADA UNTUK RANGE INI
        $existing = PieceworkPayrollPeriod::query()
            ->where('module', 'cutting')
            ->whereDate('period_start', $data['period_start'])
            ->whereDate('period_end', $data['period_end'])
            ->first();

        if ($existing && $existing->status === 'final') {
            // PERIODE SUDAH FINAL → TIDAK BOLEH DIBUAT ULANG
            return redirect()
                ->route('payroll.cutting.show', $existing)
                ->with('error', 'Periode payroll cutting ini sudah FINAL, tidak bisa digenerate ulang. Silakan pilih rentang tanggal lain.');
        }

        // Kalau ada existing draft → regenerate existing
        // Kalau tidak ada → generate periode baru
        $period = CuttingPayrollGenerator::generate(
            periodStart: $data['period_start'],
            periodEnd: $data['period_end'],
            createdByUserId: $userId,
            existingPeriod: $existing, // bisa null
        );

        $message = $existing
        ? 'Payroll Cutting untuk periode ' . id_date($period->period_start) . ' s/d ' . id_date($period->period_end) . ' berhasil di-UPDATE (regenerate).'
        : 'Payroll Cutting berhasil digenerate untuk periode ' . id_date($period->period_start) . ' s/d ' . id_date($period->period_end) . '.';

        return redirect()
            ->route('payroll.cutting.show', $period)
            ->with('status', $message);
    }

    /**
     * Detail 1 periode payroll cutting:
     * - Ringkasan total per operator
     * - Breakdown per item/category
     */
    public function show(PieceworkPayrollPeriod $period): View
    {
        // Pastikan ini periode untuk modul cutting
        if ($period->module !== 'cutting') {
            abort(404);
        }

        // Ambil semua line untuk periode ini
        $lines = PieceworkPayrollLine::query()
            ->with(['employee', 'category', 'item'])
            ->where('payroll_period_id', $period->id)
            ->orderBy('employee_id')
            ->orderBy('item_category_id')
            ->orderBy('item_id')
            ->get();
        // Ringkasan per operator
        $summaryByEmployee = $lines
            ->groupBy('employee_id')
            ->map(function ($group) {
                /** @var \Illuminate\Support\Collection $group */
                $employee = $group->first()->employee;

                return [
                    'employee_id' => $employee?->id,
                    'employee_name' => $employee?->name ?? '-',
                    'total_qty' => $group->sum('total_qty_ok'),
                    'total_amount' => $group->sum('amount'),
                ];
            })
            ->values();

        // Total keseluruhan
        $grandTotalQty = $lines->sum('total_qty_ok');
        $grandTotalAmount = $lines->sum('amount');

        return view('payroll.cutting.show', [
            'period' => $period,
            'lines' => $lines,
            'summaryByEmployee' => $summaryByEmployee,
            'grandTotalQty' => $grandTotalQty,
            'grandTotalAmount' => $grandTotalAmount,
        ]);
    }

    public function slip(PieceworkPayrollPeriod $period, $employeeId): View
    {
        if ($period->module !== 'cutting') {
            abort(404);
        }

        // Ambil semua line utk operator ini
        $lines = PieceworkPayrollLine::query()
            ->with(['employee', 'category', 'item'])
            ->where('payroll_period_id', $period->id)
            ->where('employee_id', $employeeId)
            ->orderBy('item_category_id')
            ->orderBy('item_id')
            ->get();

        if ($lines->isEmpty()) {
            abort(404, 'Operator tidak memiliki data pada periode ini.');
        }

        $employee = $lines->first()->employee;

        $totalQty = $lines->sum('total_qty_ok');
        $totalAmount = $lines->sum('amount');

        return view('payroll.cutting.slip', [
            'period' => $period,
            'employee' => $employee,
            'lines' => $lines,
            'totalQty' => $totalQty,
            'totalAmount' => $totalAmount,
        ]);
    }

    /**
     * Finalize / lock periode payroll (opsional)
     * - Misal: ubah status dari 'draft' ke 'final'
     */
    public function finalize(PieceworkPayrollPeriod $period): RedirectResponse
    {
        if ($period->module !== 'cutting') {
            abort(404);
        }

        if ($period->status === 'final') {
            return redirect()
                ->route('payroll.cutting.show', $period)
                ->with('status', 'Periode ini sudah difinalkan sebelumnya.');
        }

        $period->update([
            'status' => 'final',
            'finalized_at' => now(),
            'finalized_by' => Auth::id(),
        ]);

        return redirect()
            ->route('payroll.cutting.show', $period)
            ->with('status', 'Periode payroll cutting berhasil difinalkan.');
    }

    /**
     * (Opsional) Regenerate: hapus line lama, generate ulang untuk periode yang sama.
     */
    public function regenerate(PieceworkPayrollPeriod $period): RedirectResponse
    {
        if ($period->module !== 'cutting') {
            abort(404);
        }

        // Kalau sudah final, jangan boleh regenerate
        if ($period->status === 'final') {
            return redirect()
                ->route('payroll.cutting.show', $period)
                ->with('error', 'Periode yang sudah final tidak boleh digenerate ulang.');
        }

        // Hapus lines lama
        $period->lines()->delete();

        // Generate ulang pakai tanggal dari period yang sama
        $newPeriod = CuttingPayrollGenerator::generate(
            periodStart: $period->period_start,
            periodEnd: $period->period_end,
            createdByUserId: Auth::id()
        );

        // Optional: hapus period lama kalau mau pakai periode baru sebagai satu-satunya
        // $period->delete();

        return redirect()
            ->route('payroll.cutting.show', $newPeriod)
            ->with('status', 'Payroll Cutting berhasil digenerate ulang.');
    }
}
