<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use App\Services\Payroll\SewingPayrollGenerator;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SewingPayrollController extends Controller
{
    /**
     * Tampilkan daftar periode payroll sewing
     */
    public function index(Request $request): View
    {
        $query = PieceworkPayrollPeriod::query()
            ->where('module', 'sewing')
            ->orderByDesc('period_start');

        // Optional filter tanggal dari request (sama seperti cutting)
        if ($request->filled('from')) {
            $query->whereDate('period_start', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('period_end', '<=', $request->input('to'));
        }

        $periods = $query->paginate(15)->withQueryString();

        return view('payroll.sewing.index', [
            'periods' => $periods,
        ]);
    }

    /**
     * Form pilih periode untuk generate payroll sewing
     */
    public function create(Request $request): View
    {
        // Default periode: 1 minggu terakhir (samakan dengan cutting)
        $defaultEnd = Carbon::today();
        $defaultStart = (clone $defaultEnd)->subDays(6);

        return view('payroll.sewing.create', [
            'defaultStart' => $defaultStart->toDateString(),
            'defaultEnd' => $defaultEnd->toDateString(),
        ]);
    }

    /**
     * Proses generate payroll sewing untuk periode tertentu
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

        // Panggil service generator (akan buat PieceworkPayrollPeriod + Lines)
        $period = SewingPayrollGenerator::generate(
            periodStart: $data['period_start'],
            periodEnd: $data['period_end'],
            createdByUserId: $userId,
        );

        return redirect()
            ->route('payroll.sewing.show', $period)
            ->with(
                'status',
                'Payroll Sewing berhasil digenerate untuk periode '
                . $period->period_start . ' s/d ' . $period->period_end
            );
    }

    /**
     * Detail 1 periode payroll sewing:
     * - Ringkasan total per operator
     * - Breakdown per item/category
     */
    public function show(PieceworkPayrollPeriod $period): View
    {
        // Pastikan ini periode untuk modul sewing
        if ($period->module !== 'sewing') {
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

        return view('payroll.sewing.show', [
            'period' => $period,
            'lines' => $lines,
            'summaryByEmployee' => $summaryByEmployee,
            'grandTotalQty' => $grandTotalQty,
            'grandTotalAmount' => $grandTotalAmount,
        ]);
    }

    /**
     * Finalize / lock periode payroll sewing
     */
    public function finalize(PieceworkPayrollPeriod $period): RedirectResponse
    {
        if ($period->module !== 'sewing') {
            abort(404);
        }

        if ($period->status === 'final') {
            return redirect()
                ->route('payroll.sewing.show', $period)
                ->with('status', 'Periode ini sudah difinalkan sebelumnya.');
        }

        $period->update([
            'status' => 'final',
            'finalized_at' => now(),
            'finalized_by' => Auth::id(),
        ]);

        return redirect()
            ->route('payroll.sewing.show', $period)
            ->with('status', 'Periode payroll sewing berhasil difinalkan.');
    }

    /**
     * Regenerate: hapus line lama, generate ulang untuk periode yang sama.
     */
    public function regenerate(PieceworkPayrollPeriod $period): RedirectResponse
    {
        if ($period->module !== 'sewing') {
            abort(404);
        }

        // Kalau sudah final, jangan boleh regenerate
        if ($period->status === 'final') {
            return redirect()
                ->route('payroll.sewing.show', $period)
                ->with('error', 'Periode yang sudah final tidak boleh digenerate ulang.');
        }

        // Hapus semua lines lama
        $period->lines()->delete();

        // Generate ulang pakai tanggal dari period yang sama
        $newPeriod = SewingPayrollGenerator::generate(
            periodStart: $period->period_start,
            periodEnd: $period->period_end,
            createdByUserId: Auth::id(),
        );

        // (Opsional) hapus period lama jika ingin 1 periode saja:
        // $period->delete();

        return redirect()
            ->route('payroll.sewing.show', $newPeriod)
            ->with('status', 'Payroll Sewing berhasil digenerate ulang.');
    }
}
