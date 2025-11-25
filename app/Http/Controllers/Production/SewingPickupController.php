<?php
namespace App\Http\Controllers\Production;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SewingPickupController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    public function index(Request $request)
    {
        $pickups = SewingPickup::query()
            ->with([
                'warehouse',
                'operator',
                'lines',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('production.sewing_pickups.index', [
            'pickups' => $pickups,
        ]);
    }

    public function show(SewingPickup $pickup)
    {
        $pickup->load([
            'warehouse',
            'operator',
            'lines.bundle.finishedItem',
            'lines.bundle.cuttingJob.lot.item',
        ]);

        return view('production.sewing_pickups.show', [
            'pickup' => $pickup,
        ]);
    }

    public function bundlesReady()
    {
        $bundles = CuttingJobBundle::query()
            ->with([
                'finishedItem',
                'operator',
                'cuttingJob.lot.item',
                'qcResults' => fn($q) => $q->where('stage', 'cutting'),
            ])
            ->whereIn('status', ['qc_ok', 'qc_mixed']) // ready to sew
            ->orderBy('id')
            ->get();

        return view('production.sewing_pickups.bundle_ready', [
            'bundles' => $bundles,
        ]);
    }

    public function create()
    {
        $operators = Employee::where('role', 'sewing')
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::orderBy('code')->get();

        $wipCutId = Warehouse::where('code', 'WIP-CUT')->value('id');
        $bundles = CuttingJobBundle::readyForSewing($wipCutId)
            ->with(['finishedItem', 'cuttingJob.lot.item'])
            ->get();

        return view('production.sewing_pickups.create', [
            'operators' => $operators,
            'warehouses' => $warehouses,
            'bundles' => $bundles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'], // gudang sewing (WIP-SEW)
            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.bundle_id' => ['required', 'exists:cutting_job_bundles,id'],
            'lines.*.qty_bundle' => ['required', 'numeric', 'min:0'], // boleh 0, nanti di-skip
        ], [
            'lines.required' => 'Minimal satu baris bundle harus diisi.',
            'lines.*.bundle_id.required' => 'Bundle tidak valid.',
            'lines.*.qty_bundle.required' => 'Qty pickup wajib diisi.',
        ]);

        DB::transaction(function () use ($validated) {

            // 🔎 Cari gudang WIP-CUT (sumber stok WIP Cutting)
            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

            if (!$wipCutWarehouseId) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Gudang WIP-CUT belum dikonfigurasi. Pastikan ada warehouse dengan code "WIP-CUT".',
                ]);
            }

            $sewingWarehouseId = (int) $validated['warehouse_id'];
            $date = $validated['date'];

            $code = CodeGenerator::generate('SWP');

            /** @var SewingPickup $pickup */
            $pickup = SewingPickup::create([
                'code' => $code,
                'date' => $date,
                'warehouse_id' => $sewingWarehouseId,
                'operator_id' => $validated['operator_id'],
                'status' => 'draft', // nanti bisa ada tombol POSTED
                'notes' => $validated['notes'] ?? null,
            ]);

            $createdLines = 0;

            foreach ($validated['lines'] as $row) {
                $qty = (float) ($row['qty_bundle'] ?? 0);
                if ($qty <= 0) {
                    continue; // baris kosong, skip
                }

                // Ambil bundle + QC Cutting (stage = cutting)
                $bundle = CuttingJobBundle::with([
                    'qcResults' => function ($q) {
                        $q->where('stage', 'cutting');
                    },
                    'cuttingJob',
                ])
                    ->find($row['bundle_id']);

                if (!$bundle) {
                    continue;
                }

                // QC Cutting terakhir untuk bundle ini
                $lastQc = $bundle->qcResults
                    ->sortByDesc('qc_date')
                    ->first();

                // Batas maksimum qty pickup:
                // - kalau ada QC → pakai qty_ok
                // - kalau belum ada QC → fallback ke qty_pcs
                if ($lastQc && $lastQc->qty_ok !== null) {
                    $maxQty = (float) $lastQc->qty_ok;
                } else {
                    $maxQty = (float) $bundle->qty_pcs;
                }

                if ($maxQty <= 0) {
                    // tidak ada qty OK yang bisa dijahit
                    continue;
                }

                if ($qty > $maxQty) {
                    $qty = $maxQty;
                }

                // 🔹 Simpan detail sewing pickup
                SewingPickupLine::create([
                    'sewing_pickup_id' => $pickup->id,
                    'cutting_job_bundle_id' => $bundle->id,
                    'finished_item_id' => $bundle->finished_item_id,
                    'qty_bundle' => $qty,
                    'status' => 'in_progress',
                ]);

                // 🔹 INVENTORY: WIP-CUT → WIP-SEW
                $notes = "Sewing pickup {$pickup->code} - bundle {$bundle->bundle_code}";

                // 1) Keluar dari WIP-CUT (barang WIP Cutting)
                $this->inventory->stockOut(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $bundle->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: 'sewing_pickup',
                    sourceId: $pickup->id,
                    notes: $notes,
                    allowNegative: false,
                    lotId: $bundle->lot_id, // penting: untuk cost & trace LOT
                );

                // 2) Masuk ke gudang sewing (misal WIP-SEW)
                $this->inventory->stockIn(
                    warehouseId: $sewingWarehouseId,
                    itemId: $bundle->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: 'sewing_pickup',
                    sourceId: $pickup->id,
                    notes: $notes,
                    lotId: $bundle->lot_id,
                    unitCost: null, // biarkan ikut moving average LOT, jangan bikin layer baru
                );

                $createdLines++;
            }

            // Kalau tidak ada satupun line valid, batal & lempar error
            if ($createdLines === 0) {
                throw ValidationException::withMessages([
                    'lines' => 'Minimal satu bundle harus punya Qty Pickup > 0 dan qty OK dari QC.',
                ]);
            }
        });

        return redirect()
            ->route('production.sewing_pickups.index')
            ->with('success', 'Sewing pickup berhasil dibuat dan stok sudah dipindahkan dari WIP-CUT ke gudang sewing.');
    }

}
