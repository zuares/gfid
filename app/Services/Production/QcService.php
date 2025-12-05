<?php

namespace App\Services\Production;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\FinishingJob;
use App\Models\QcResult;
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
     */
    public function saveCuttingQc(CuttingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $rows = $payload['results'] ?? [];

            // Gudang RM tempat LOT berada
            $rmWarehouseId = $job->warehouse_id;

            // Gudang WIP-CUT
            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

            if (!$rmWarehouseId || !$wipCutWarehouseId) {
                throw new \RuntimeException('Warehouse RM atau WIP-CUT belum dikonfigurasi.');
            }

            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id');

            $totalOkByFinishedItem = []; // [finished_item_id => total_qty_ok]
            $hasAnyOk = false;

            // ===========================
            // 1) LOOP HASIL QC PER BUNDLE
            // ===========================
            foreach ($rows as $row) {
                $bundleId = (int) ($row['bundle_id'] ?? $row['cutting_job_bundle_id'] ?? 0);

                if ($bundleId <= 0) {
                    continue;
                }

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    continue;
                }

                $bundleQty = (float) $bundle->qty_pcs;

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

                // 1.a Simpan / update qc_results
                QcResult::updateOrCreate(
                    [
                        'stage' => 'cutting',
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
                $bundle->save();

                // 1.c Akumulasi qty_ok per finished_item
                if ($bundle->finished_item_id && $qtyOk > 0) {
                    $totalOkByFinishedItem[$bundle->finished_item_id] =
                        ($totalOkByFinishedItem[$bundle->finished_item_id] ?? 0) + $qtyOk;

                    $hasAnyOk = true;
                }
            }

            if (!$hasAnyOk) {
                return;
            }

            // ===========================
            // 2) INVENTORY MOVEMENT + COST
            // ===========================
            $lot = $job->lot;

            if (!$lot) {
                throw new \RuntimeException("CuttingJob {$job->id} tidak memiliki LOT terkait.");
            }

            // total OK semua FG (basis alokasi cost RM)
            $totalOkAll = array_sum($totalOkByFinishedItem);

            // saldo LOT kain yang mau dihabiskan
            $lotQty = $this->inventory->getLotBalance(
                warehouseId: $rmWarehouseId,
                itemId: $lot->item_id,
                lotId: $lot->id,
            );

            // hitung RM cost per PCS FG (jika memungkinkan)
            $rmUnitCostPerUnit = null;

            if ($lotQty > 0 && $totalOkAll > 0) {
                $rmUnitCostLot = $this->inventory->getLotMovingAverageUnitCost(
                    warehouseId: $rmWarehouseId,
                    itemId: $lot->item_id,
                    lotId: $lot->id,
                );

                if ($rmUnitCostLot !== null) {
                    $totalRmCost = $rmUnitCostLot * $lotQty; // total nilai kain
                    $rmUnitCostPerUnit = $totalRmCost / $totalOkAll; // Rp/pcs FG
                }
            }

            // 2.a STOCK OUT: habiskan saldo LOT di gudang RM
            if ($lotQty > 0) {
                $this->inventory->stockOut(
                    warehouseId: $rmWarehouseId,
                    itemId: $lot->item_id,
                    qty: $lotQty,
                    date: $qcDate,
                    sourceType: 'cutting_qc_out',
                    sourceId: $job->id,
                    notes: "QC Cutting OUT full saldo LOT {$lotQty} untuk job {$job->code}",
                    allowNegative: false,
                    lotId: $lot->id,
                    // biarkan costing OUT tetap pakai LotCostService (affectLotCost default = true)
                );
            }

            // 2.b STOCK IN: WIP-CUT per finished_item (pcs OK) — pakai RM cost/pcs kalau ada
            foreach ($totalOkByFinishedItem as $finishedItemId => $qtyOkItem) {
                if ($qtyOkItem <= 0) {
                    continue;
                }

                $this->inventory->stockIn(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $finishedItemId,
                    qty: $qtyOkItem,
                    date: $qcDate,
                    sourceType: 'cutting_qc_in',
                    sourceId: $job->id,
                    notes: "QC Cutting IN WIP-CUT {$qtyOkItem} pcs untuk job {$job->code}",
                    lotId: null,
                    unitCost: $rmUnitCostPerUnit, // 🔥 inilah HPP RM per pcs di WIP-CUT
                    affectLotCost: false,
                );
            }
        });
    }

    /* ============================================================
     * 2) QC SEWING
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

            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id');

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

                $bundleQtyBase = (float) ($bundle->qty_qc_ok ?? $bundle->qty_pcs ?? 0);

                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                if ($qtyOk < 0) {
                    $qtyOk = 0;
                }
                if ($qtyReject < 0) {
                    $qtyReject = 0;
                }

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
                    stage: QcResult::STAGE_SEWING,
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

            if (!$hasAnyMovement) {
                return;
            }

            // ===========================
            // 2) INVENTORY MOVEMENT + COST
            // ===========================
            // - OUT: WIP-SEW (OK + Reject) pakai cost avg WIP-SEW
            // - IN : WIP-FIN (OK) pakai cost yang sama
            // - IN : REJ-SEW (Reject) pakai cost yang sama

            // siapkan map unit_cost di WIP-SEW per item
            $unitCostWipSewPerItem = [];
            foreach (array_keys($totalProcessedByItem) as $itemId) {
                $unit = $this->inventory->getItemIncomingUnitCost($wipSewWarehouseId, $itemId);
                $unitCostWipSewPerItem[$itemId] = $unit > 0 ? $unit : null;
            }

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
                    lotId: null,
                    unitCostOverride: $unitCostWipSewPerItem[$itemId] ?? null,
                    affectLotCost: false,
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
                    lotId: null,
                    unitCost: $unitCostWipSewPerItem[$itemId] ?? null,
                    affectLotCost: false,
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
                    lotId: null,
                    unitCost: $unitCostWipSewPerItem[$itemId] ?? null,
                    affectLotCost: false,
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
            $bundleMap = $job->bundles()->get()->keyBy('id');

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

        if ($stage === QcResult::STAGE_CUTTING) {
            $bundle->qty_qc_ok = $qtyOk;
            $bundle->qty_qc_reject = $qtyReject;
            $bundle->status = $status;
            $bundle->save();
        }
    }

    protected function resolveBundleStatus(float $qtyOk, float $qtyReject, float $bundleQty): string
    {
        if ($qtyOk <= 0 && $qtyReject <= 0) {
            return 'cut';
        }

        if ($qtyOk > 0 && $qtyReject <= 0) {
            return 'qc_ok';
        }

        if ($qtyOk > 0 && $qtyReject > 0) {
            return 'qc_mixed';
        }

        return 'qc_reject';
    }
}
