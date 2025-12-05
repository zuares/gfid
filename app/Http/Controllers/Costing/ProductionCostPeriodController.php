<?php

namespace App\Http\Controllers\Costing;

use App\Http\Controllers\Controller;
use App\Models\ItemCostSnapshot;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use App\Models\ProductionCostPeriod;
use App\Services\Costing\HppService;
use App\Services\Costing\ProductionCostService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionCostPeriodController extends Controller
{
    public function __construct(
        protected HppService $hpp,
        protected ProductionCostService $productionCost,
    ) {}

    /**
     * List periode costing produksi.
     */
    public function index(Request $request): View
    {
        $periods = ProductionCostPeriod::query()
            ->with([
                'cuttingPayrollPeriod',
                'sewingPayrollPeriod',
                'finishingPayrollPeriod',
            ])
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('costing.production_cost_periods.index', [
            'periods' => $periods,
        ]);
    }

    /**
     * Detail satu periode + hasil HPP per item (dibaca dari item_cost_snapshots).
     */
    public function show(ProductionCostPeriod $period): View
    {
        $period->load([
            'cuttingPayrollPeriod',
            'sewingPayrollPeriod',
            'finishingPayrollPeriod',
        ]);

        // Ambil semua snapshot yang refer ke periode ini
        $snapshots = ItemCostSnapshot::query()
            ->with('item')
            ->where('reference_type', 'production_cost_period')
            ->where('reference_id', $period->id)
            ->orderBy('item_id')
            ->get();

        return view('costing.production_cost_periods.show', [
            'period' => $period,
            'snapshots' => $snapshots,
        ]);
    }

    /**
     * Jalankan generate HPP dari payroll untuk 1 periode.
     */
    public function generate(ProductionCostPeriod $period, Request $request): RedirectResponse
    {
        // Opsional: kalau mau larang re-generate, bisa cek status
        // if ($period->status === 'posted') { ... }

        $results = $this->productionCost->generateFromPayroll($period);

        return redirect()
            ->route('costing.production_cost_periods.show', $period)
            ->with('status', "HPP periode {$period->code} berhasil digenerate untuk " . count($results) . " item.")
            ->with('generate_results', $results); // kalau mau dipakai di flash di view
    }

    public function generateFromPayroll(ProductionCostPeriod $period): array
    {
        // ⚠️ Sesuaikan nama kolom tanggal di production_cost_periods
        $dateFrom = Carbon::parse($period->date_from)->startOfDay();
        $dateTo = Carbon::parse($period->date_to)->endOfDay();

        // 1️⃣ Ambil semua payroll period (cutting + sewing) yang FINAL di range periode ini
        //    Asumsi: tabel piecework_payroll_periods:
        //    - module: 'cutting' | 'sewing'
        //    - status: 'draft' | 'final'
        //    - date_from, date_to
        $payrollAgg = PieceworkPayrollLine::query()
            ->join('piecework_payroll_periods as p', 'p.id', '=', 'piecework_payroll_lines.payroll_period_id')
            ->where('p.status', 'final')
            ->whereIn('p.module', ['cutting', 'sewing'])
            ->whereDate('p.date_from', '>=', $dateFrom->toDateString())
            ->whereDate('p.date_to', '<=', $dateTo->toDateString())
            ->whereNotNull('piecework_payroll_lines.item_id') // hanya yang sudah mapping ke item FG
            ->selectRaw('
                piecework_payroll_lines.item_id      as item_id,
                p.module                             as module,
                SUM(piecework_payroll_lines.amount)  as total_amount,
                SUM(piecework_payroll_lines.total_qty_ok) as total_qty_ok
            ')
            ->groupBy('piecework_payroll_lines.item_id', 'p.module')
            ->get();

        if ($payrollAgg->isEmpty()) {
            return [];
        }

        // 2️⃣ Susun agregat per item: pisahkan cutting vs sewing
        //    Struktur: [item_id => [...]]
        $byItem = [];

        foreach ($payrollAgg as $row) {
            $itemId = (int) $row->item_id;
            if ($itemId <= 0) {
                continue;
            }

            if (!isset($byItem[$itemId])) {
                $byItem[$itemId] = [
                    'total_cutting_amount' => 0.0,
                    'total_cutting_qty' => 0.0,
                    'total_sewing_amount' => 0.0,
                    'total_sewing_qty' => 0.0,
                ];
            }

            if ($row->module === 'cutting') {
                $byItem[$itemId]['total_cutting_amount'] += (float) $row->total_amount;
                $byItem[$itemId]['total_cutting_qty'] += (float) $row->total_qty_ok;
            } elseif ($row->module === 'sewing') {
                $byItem[$itemId]['total_sewing_amount'] += (float) $row->total_amount;
                $byItem[$itemId]['total_sewing_qty'] += (float) $row->total_qty_ok;
            }
        }

        // 3️⃣ Untuk tiap item: hitung unit cost cutting + sewing,
        //     combine dengan RM dari snapshot aktif.
        $results = [];

        foreach ($byItem as $itemId => $agg) {
            $cutAmount = (float) $agg['total_cutting_amount'];
            $cutQty = (float) $agg['total_cutting_qty'];
            $sewAmount = (float) $agg['total_sewing_amount'];
            $sewQty = (float) $agg['total_sewing_qty'];

            // basis qty → ambil maksimum dari cutting/sewing
            // (biar tidak double count kalau qty-nya beda sedikit)
            $qtyBasis = max($cutQty, $sewQty);

            if ($qtyBasis <= 0) {
                // tidak ada qty yang bisa dibagi, skip item ini
                continue;
            }

            $cuttingUnitCost = $cutAmount > 0 ? $cutAmount / $qtyBasis : 0.0;
            $sewingUnitCost = $sewAmount > 0 ? $sewAmount / $qtyBasis : 0.0;

            // 🔍 Ambil snapshot aktif existing → untuk baca RM unit cost
            //     (RM-only snapshot yang kamu buat waktu Finishing POSTED)
            $existing = $this->hpp->getActiveSnapshotForItem($itemId, null);

            $rmUnitCost = 0.0;

            if ($existing) {
                // Kalau sebelumnya RM-only, rm_unit_cost == unit_cost
                // Kalau nanti sudah lengkap, kita tetap pakai rm_unit_cost-nya.
                $rmUnitCost = (float) ($existing->rm_unit_cost ?? $existing->unit_cost ?? 0);
            }

            // 4️⃣ Buat snapshot baru (RM + Cutting + Sewing)
            $snapshot = $this->hpp->createSnapshot(
                itemId: $itemId,
                warehouseId: null, // global HPP (bukan per gudang), bisa diubah kalau mau
                snapshotDate: $period->date_to, // tanggal akhir periode HPP
                referenceType: 'production_cost_period',
                referenceId: $period->id,
                qtyBasis: $qtyBasis,
                rmUnitCost: $rmUnitCost,
                cuttingUnitCost: $cuttingUnitCost,
                sewingUnitCost: $sewingUnitCost,
                finishingUnitCost: 0, // kalau nanti mau tambahkan finishing payroll, tinggal isi
                packagingUnitCost: 0,
                overheadUnitCost: 0,
                notes: 'Auto HPP RM+Cutting+Sewing periode ' . $period->code,
            );

            $results[$itemId] = [
                'snapshot' => $snapshot,
                'rm_unit_cost' => $rmUnitCost,
                'cutting_unit_cost' => $cuttingUnitCost,
                'sewing_unit_cost' => $sewingUnitCost,
                'qty_basis' => $qtyBasis,
                'total_cutting_amount' => $cutAmount,
                'total_sewing_amount' => $sewAmount,
            ];
        }

        return $results;
    }
    public function edit(ProductionCostPeriod $period)
    {
        $cuttingPeriods = PieceworkPayrollPeriod::where('module', 'cutting')
            ->where('status', 'final') // atau 'posted' sesuai punyamu
            ->orderByDesc('period_start')
            ->get();

        $sewingPeriods = PieceworkPayrollPeriod::where('module', 'sewing')
            ->where('status', 'final')
            ->orderByDesc('period_start')
            ->get();

        $finishingPeriods = PieceworkPayrollPeriod::where('module', 'finishing')
            ->where('status', 'final')
            ->orderByDesc('period_start')
            ->get();

        return view('costing.production_cost_periods.edit', [
            'period' => $period,
            'cuttingPeriods' => $cuttingPeriods,
            'sewingPeriods' => $sewingPeriods,
            'finishingPeriods' => $finishingPeriods,
        ]);
    }
    public function update(Request $request, ProductionCostPeriod $period)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'snapshot_date' => ['required', 'date'],
            'cutting_payroll_period_id' => ['nullable', 'exists:piecework_payroll_periods,id'],
            'sewing_payroll_period_id' => ['nullable', 'exists:piecework_payroll_periods,id'],
            'finishing_payroll_period_id' => ['nullable', 'exists:piecework_payroll_periods,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $period->update([
            'name' => $data['name'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'snapshot_date' => $data['snapshot_date'],
            'cutting_payroll_period_id' => $data['cutting_payroll_period_id'] ?? null,
            'sewing_payroll_period_id' => $data['sewing_payroll_period_id'] ?? null,
            'finishing_payroll_period_id' => $data['finishing_payroll_period_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('costing.production_cost_periods.show', $period)
            ->with('status', 'Periode HPP berhasil diupdate dan link ke payroll disimpan.');
    }

}
