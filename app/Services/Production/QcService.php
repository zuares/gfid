<?php

namespace App\Services\Production;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\FinishingJob;
use App\Models\QcResult;
// Sesuaikan kalau kamu pakai SewingReturn / model lain
use App\Models\SewingJob;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

class QcService
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /* ============================================================
     * 1) QC CUTTING
     * ============================================================
     *
     * Desain:
     * - LOT kain (kg) sudah berada di WIP-CUT sebelum QC (dari proses lain:
     *   transfer RM → WIP-CUT atau dari CuttingService::create()).
     * - Di sini:
     *   - OUT:  kain LOT (kg) dari WIP-CUT sampai saldo LOT = 0
     *   - IN :  barang jadi OK (pcs) ke WIP-CUT [tanpa lot]
     *   - IN :  barang reject (pcs) ke REJ-CUT [tanpa lot]
     */
    public function saveCuttingQc(CuttingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $rows = $payload['results'] ?? [];

            // Gudang RM tempat LOT berada (diasumsikan di field warehouse_id di CuttingJob)
            $rmWarehouseId = $job->warehouse_id;

            // Gudang WIP-CUT (pastikan ada di tabel warehouses dengan code = 'WIP-CUT')
            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

            if (!$rmWarehouseId || !$wipCutWarehouseId) {
                throw new \RuntimeException('Warehouse RM atau WIP-CUT belum dikonfigurasi.');
            }

            // Ambil semua bundle di job ini sebagai map [bundle_id => model]
            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id');

            // Akumulasi hasil QC untuk posting ke inventory
            $totalOkByFinishedItem = []; // [finished_item_id => total_qty_ok]
            $hasAnyOk = false;

            // ===========================
            // 1) LOOP HASIL QC PER BUNDLE
            // ===========================
            foreach ($rows as $row) {
                // Support kedua nama key: 'bundle_id' atau 'cutting_job_bundle_id'
                $bundleId = (int) ($row['bundle_id'] ?? $row['cutting_job_bundle_id'] ?? 0);

                if ($bundleId <= 0) {
                    continue;
                }

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    // bundle bukan milik job ini → skip
                    continue;
                }

                $bundleQty = (float) $bundle->qty_pcs;

                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                // Normalisasi (tidak boleh negatif)
                if ($qtyOk < 0) {
                    $qtyOk = 0;
                }
                if ($qtyReject < 0) {
                    $qtyReject = 0;
                }

                // Clamp supaya tidak melebihi qty bundle
                if ($qtyOk + $qtyReject > $bundleQty) {
                    $diff = ($qtyOk + $qtyReject) - $bundleQty;

                    // Kurangi reject dulu, lalu ok
                    if ($qtyReject >= $diff) {
                        $qtyReject -= $diff;
                    } else {
                        $qtyOk = max(0, $bundleQty - $qtyReject);
                    }
                }

                $status = $this->resolveBundleStatus($qtyOk, $qtyReject, $bundleQty);

                // 1.a Simpan / update qc_results
                QcResult::updateOrCreate(
                    [
                        'stage' => 'cutting', // atau QcResult::STAGE_CUTTING kalau kamu punya constant
                        'cutting_job_id' => $job->id,
                        'cutting_job_bundle_id' => $bundleId,
                    ],
                    [
                        'qc_date' => $qcDate,
                        'qty_ok' => $qtyOk,
                        'qty_reject' => $qtyReject,
                        'operator_id' => $operatorId,
                        'status' => $status,
                        'notes' => $row['notes'] ?? null,
                    ],
                );

                // 1.b Update field QC di bundle
                $bundle->qty_qc_ok = $qtyOk;
                $bundle->qty_qc_reject = $qtyReject;
                $bundle->status = $status;

                // ⚠ Di tahap cutting: JANGAN isi wip_warehouse_id / wip_qty.
                // Field WIP di bundle disiapkan untuk alur berikutnya (sewing/finishing).
                $bundle->save();

                // 1.c Akumulasi qty_ok per finished_item untuk WIP-CUT
                if ($bundle->finished_item_id && $qtyOk > 0) {
                    $totalOkByFinishedItem[$bundle->finished_item_id] =
                        ($totalOkByFinishedItem[$bundle->finished_item_id] ?? 0) + $qtyOk;

                    $hasAnyOk = true;
                }
            }

            // Kalau tidak ada qty OK sama sekali → tidak ada pergerakan inventory
            if (!$hasAnyOk) {
                return;
            }

            // ===========================
            // 2) INVENTORY MOVEMENT
            // ===========================
            $lot = $job->lot;

            if (!$lot) {
                throw new \RuntimeException("CuttingJob {$job->id} tidak memiliki LOT terkait.");
            }

            // 2.a Ambil saldo LOT aktual di gudang RM
            $lotQty = $this->inventory->getLotBalance(
                warehouseId: $rmWarehouseId,
                itemId: $lot->item_id,
                lotId: $lot->id,
            );

            // Kalau saldo LOT sudah 0, kita tetap lanjut WIP IN atau mau di-skip total.
            // Di sini aku pilih: kalau 0 → tetap lanjut WIP IN (supaya stok hasil tetap tercatat).
            if ($lotQty > 0) {
                // 2.b STOCK OUT: habiskan saldo LOT di gudang RM
                $this->inventory->stockOut(
                    warehouseId: $rmWarehouseId,
                    itemId: $lot->item_id, // kain mentah
                    qty: $lotQty,
                    date: $qcDate,
                    sourceType: 'cutting_qc_out',
                    sourceId: $job->id,
                    notes: "QC Cutting OUT full saldo LOT {$lotQty} untuk job {$job->code}",
                    allowNegative: false,
                    lotId: $lot->id,
                );
            }

            // 2.c STOCK IN: WIP-CUT per finished_item (pcs hasil OK) — TANPA LOT
            foreach ($totalOkByFinishedItem as $finishedItemId => $qtyOkItem) {
                if ($qtyOkItem <= 0) {
                    continue;
                }

                $this->inventory->stockIn(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $finishedItemId, // barang WIP hasil cutting
                    qty: $qtyOkItem, // total OK (pcs) per item
                    date: $qcDate,
                    sourceType: 'cutting_qc_in',
                    sourceId: $job->id,
                    notes: "QC Cutting IN WIP-CUT {$qtyOkItem} pcs untuk job {$job->code}",
                    lotId: null, // ✅ hasil cutting TIDAK pakai LOT kain
                    unitCost: null, // bisa diisi cost per pcs kalau nanti mau
                );
            }
        });
    }

    /* ============================================================
     * 2) QC SEWING (KERANGKA – NANTI DISINKRONKAN
     *    DENGAN ALUR WIP-CUT → WIP-SEW → WIP-FIN)
     * ============================================================
     */
    public function saveSewingQc(SewingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $rows = $payload['results'] ?? [];

            // ===========================
            // 0) WAREHOUSE SETUP
            // ===========================
            $wipSewWarehouseId = Warehouse::where('code', 'WIP-SEW')->value('id');
            $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');
            $rejSewWarehouseId = Warehouse::where('code', 'REJ-SEW')->value('id');

            if (!$wipSewWarehouseId || !$wipFinWarehouseId || !$rejSewWarehouseId) {
                throw new \RuntimeException('Warehouse WIP-SEW / WIP-FIN / REJ-SEW belum dikonfigurasi.');
            }

            // Ambil bundle yang terkait job sewing ini
            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id');

            // Akumulasi qty untuk mutasi stok
            $totalProcessedByItem = []; // [item_id => total (OK+Reject)]
            $totalOkByItem = []; // [item_id => total OK]
            $totalRejectByItem = []; // [item_id => total Reject]
            $hasAnyMovement = false;

            // ===========================
            // 1) LOOP HASIL QC PER BUNDLE
            // ===========================
            foreach ($rows as $row) {

                if (empty($row['bundle_id'])) {
                    continue;
                }

                $bundleId = (int) $row['bundle_id'];

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    continue;
                }

                // Dasar qty yang boleh di-QC:
                // gunakan qty_qc_ok cutting, kalau kosong fallback ke qty_pcs
                $bundleQtyBase = (float) ($bundle->qty_qc_ok ?? $bundle->qty_pcs ?? 0);

                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                // Normalisasi (tidak boleh negatif)
                if ($qtyOk < 0) {
                    $qtyOk = 0;
                }
                if ($qtyReject < 0) {
                    $qtyReject = 0;
                }

                // Clamp: OK + Reject tidak boleh > qty dasar
                if ($qtyOk + $qtyReject > $bundleQtyBase) {
                    $diff = ($qtyOk + $qtyReject) - $bundleQtyBase;

                    if ($qtyReject >= $diff) {
                        $qtyReject -= $diff;
                    } else {
                        $qtyOk = max(0, $bundleQtyBase - $qtyReject);
                    }
                }

                $status = $this->resolveBundleStatus($qtyOk, $qtyReject, $bundleQtyBase);
                $rejectReason = $row['reject_reason'] ?? null;
                $notes = $row['notes'] ?? null;

                // 1.a Simpan QC ke qc_results (stage sewing)
                $this->upsertBundleQc(
                    stage: QcResult::STAGE_SEWING, // atau 'sewing' kalau belum pakai constant
                    bundle: $bundle,
                    qcDate: $qcDate,
                    qtyOk: $qtyOk,
                    qtyReject: $qtyReject,
                    status: $status,
                    operatorId: $operatorId,
                    notes: $notes,
                    rejectReason: $rejectReason,
                    cuttingJobId: $bundle->cutting_job_id,
                    sewingJobId: $job->id,
                    finishingJobId: null,
                );

                // 1.b Akumulasi untuk mutasi stok
                if ($bundle->finished_item_id) {
                    $itemId = $bundle->finished_item_id;
                    $processedQty = $qtyOk + $qtyReject;

                    if ($processedQty > 0) {
                        $totalProcessedByItem[$itemId] =
                            ($totalProcessedByItem[$itemId] ?? 0) + $processedQty;
                        $hasAnyMovement = true;
                    }

                    if ($qtyOk > 0) {
                        $totalOkByItem[$itemId] =
                            ($totalOkByItem[$itemId] ?? 0) + $qtyOk;
                    }

                    if ($qtyReject > 0) {
                        $totalRejectByItem[$itemId] =
                            ($totalRejectByItem[$itemId] ?? 0) + $qtyReject;
                    }
                }
            }

            // Kalau tidak ada qty yang bergerak sama sekali → tidak ada mutasi stok
            if (!$hasAnyMovement) {
                return;
            }

            // ===========================
            // 2) INVENTORY MOVEMENT
            // ===========================
            // Desain:
            // - OUT: WIP-SEW (OK + Reject)
            // - IN : WIP-FIN (OK)
            // - IN : REJ-SEW (Reject)
            // Semua TANPA LOT.

            // 2.a OUT dari WIP-SEW
            foreach ($totalProcessedByItem as $itemId => $qtyProcessed) {
                if ($qtyProcessed <= 0) {
                    continue;
                }

                $this->inventory->stockOut(
                    warehouseId: $wipSewWarehouseId,
                    itemId: $itemId,
                    qty: $qtyProcessed,
                    date: $qcDate,
                    sourceType: 'sewing_qc_out',
                    sourceId: $job->id,
                    notes: "QC Sewing OUT {$qtyProcessed} pcs dari WIP-SEW untuk job {$job->code}",
                    allowNegative: false,
                    lotId: null, // ✅ WIP tidak pakai LOT
                );
            }

            // 2.b IN ke WIP-FIN (OK)
            foreach ($totalOkByItem as $itemId => $qtyOkItem) {
                if ($qtyOkItem <= 0) {
                    continue;
                }

                $this->inventory->stockIn(
                    warehouseId: $wipFinWarehouseId,
                    itemId: $itemId,
                    qty: $qtyOkItem,
                    date: $qcDate,
                    sourceType: 'sewing_qc_in',
                    sourceId: $job->id,
                    notes: "QC Sewing IN WIP-FIN {$qtyOkItem} pcs untuk job {$job->code}",
                    lotId: null, // ✅ tetap tanpa LOT
                    unitCost: null// costing bisa diisi nanti kalau sudah siap
                );
            }

            // 2.c IN ke REJ-SEW (Reject)
            foreach ($totalRejectByItem as $itemId => $qtyRejectItem) {
                if ($qtyRejectItem <= 0) {
                    continue;
                }

                $this->inventory->stockIn(
                    warehouseId: $rejSewWarehouseId,
                    itemId: $itemId,
                    qty: $qtyRejectItem,
                    date: $qcDate,
                    sourceType: 'sewing_qc_reject',
                    sourceId: $job->id,
                    notes: "QC Sewing REJECT {$qtyRejectItem} pcs untuk job {$job->code}",
                    lotId: null, // ✅ reject juga nggak pakai LOT
                    unitCost: null
                );
            }
        });
    }

    /* ============================================================
     * 3) QC FINISHING (KERANGKA)
     * ============================================================
     */
    public function saveFinishingQc(FinishingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $rows = $payload['results'] ?? [];

            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id'); // pastikan relasi di FinishingJob

            foreach ($rows as $row) {
                if (empty($row['bundle_id'])) {
                    continue;
                }

                $bundleId = (int) $row['bundle_id'];

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    continue;
                }

                // Misal pakai qty_sewing_ok sebagai dasar
                $bundleQty = (float) ($bundle->qty_sewing_ok ?? 0);

                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                if ($qtyOk < 0) {
                    $qtyOk = 0;
                }
                if ($qtyReject < 0) {
                    $qtyReject = 0;
                }

                if ($qtyOk + $qtyReject > $bundleQty) {
                    $diff = ($qtyOk + $qtyReject) - $bundleQty;

                    if ($qtyReject >= $diff) {
                        $qtyReject -= $diff;
                    } else {
                        $qtyOk = max(0, $bundleQty - $qtyReject);
                    }
                }

                $status = $this->resolveBundleStatus($qtyOk, $qtyReject, $bundleQty);
                $rejectReason = $row['reject_reason'] ?? null;
                $notes = $row['notes'] ?? null;

                $this->upsertBundleQc(
                    stage: QcResult::STAGE_FINISHING,
                    bundle: $bundle,
                    qcDate: $qcDate,
                    qtyOk: $qtyOk,
                    qtyReject: $qtyReject,
                    status: $status,
                    operatorId: $operatorId,
                    notes: $notes,
                    rejectReason: $rejectReason,
                    cuttingJobId: $bundle->cutting_job_id,
                    sewingJobId: null,
                    finishingJobId: $job->id,
                );

                // TODO:
                // - OUT dari WIP-FIN
                // - IN ke FG (OK) + REJ-FIN (reject)
                // - Tetap tanpa lot untuk barang jadi
            }
        });
    }

    /* ============================================================
     * HELPER UMUM QC PER BUNDLE
     * ============================================================
     */
    protected function upsertBundleQc(
        string $stage,
        CuttingJobBundle $bundle,
        string $qcDate,
        float $qtyOk,
        float $qtyReject,
        string $status,
        ?int $operatorId = null,
        ?string $notes = null,
        ?string $rejectReason = null,
        ?int $cuttingJobId = null,
        ?int $sewingJobId = null,
        ?int $finishingJobId = null,
    ): void {
        QcResult::updateOrCreate(
            [
                'stage' => $stage,
                'cutting_job_bundle_id' => $bundle->id,
                'cutting_job_id' => $cuttingJobId,
                'sewing_job_id' => $sewingJobId,
                'finishing_job_id' => $finishingJobId,
            ],
            [
                'qc_date' => $qcDate,
                'qty_ok' => $qtyOk,
                'qty_reject' => $qtyReject,
                'reject_reason' => $rejectReason,
                'operator_id' => $operatorId,
                'status' => $status,
                'notes' => $notes,
            ],
        );

        // ✅ Untuk sekarang, hanya update field QC di bundle saat stage CUTTING
        if ($stage === QcResult::STAGE_CUTTING) {
            $bundle->qty_qc_ok = $qtyOk;
            $bundle->qty_qc_reject = $qtyReject;
            $bundle->status = $status;
            $bundle->save();
        }
    }

    /**
     * Menentukan status bundle berdasarkan hasil QC.
     */
    protected function resolveBundleStatus(float $qtyOk, float $qtyReject, float $bundleQty): string
    {
        if ($qtyOk <= 0 && $qtyReject <= 0) {
            return 'cut'; // belum ada hasil QC
        }

        if ($qtyOk > 0 && $qtyReject <= 0) {
            return 'qc_ok';
        }

        if ($qtyOk > 0 && $qtyReject > 0) {
            return 'qc_mixed';
        }

        // qtyOk = 0, qtyReject > 0
        return 'qc_reject';
    }
}
