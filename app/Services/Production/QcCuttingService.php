<?php

namespace App\Services\Production;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\QcResult;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

class QcCuttingService
{
    public function __construct(
        protected InventoryService $inventory,
    ) {
    }

    /**
     * Simpan hasil QC Cutting:
     * - simpan / update qc_results per bundle
     * - update qty_qc_ok / qty_qc_reject + status + WIP info di cutting_job_bundles
     * - update status header CuttingJob
     * - POST inventory: OUT RM LOT -> IN WIP-CUT (qty OK per bundle)
     *
     * Catatan:
     *  - Saat ini diasumsikan QC final (sekali submit).
     *    Kalau akan sering di-edit setelah inventory, nanti kita upgrade
     *    supaya hitung delta qty OK.
     */
    public function saveCuttingQc(CuttingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {
            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $results = $payload['results'] ?? [];

            // map bundle by id biar gampang
            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundles */
            $bundles = $job->bundles()->get()->keyBy('id');

            // gudang tujuan WIP-CUT dipakai untuk WIP bundle + inventory
            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');
            if (!$wipCutWarehouseId) {
                throw new \RuntimeException('Warehouse WIP-CUT tidak ditemukan.');
            }

            $totalOk = 0.0;
            $totalReject = 0.0;

            foreach ($results as $row) {
                $bundleId = (int) $row['bundle_id'];

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $bundles->get($bundleId);
                if (!$bundle) {
                    continue;
                }

                $qtyOk = $this->num($row['qty_ok'] ?? 0);
                $qtyReject = $this->num($row['qty_reject'] ?? 0);

                // jangan sampai OK+Reject melebihi qty_pcs bundle
                $max = (float) $bundle->qty_pcs;
                if ($qtyOk + $qtyReject > $max && $max > 0) {
                    $ratio = $max / ($qtyOk + $qtyReject);
                    $qtyOk = round($qtyOk * $ratio, 2);
                    $qtyReject = round($qtyReject * $ratio, 2);
                }

                // simpan / update qc_results (1 row per bundle & stage cutting)
                QcResult::updateOrCreate(
                    [
                        'stage' => 'cutting',
                        'cutting_job_id' => $job->id,
                        'cutting_job_bundle_id' => $bundle->id,
                    ],
                    [
                        'qc_date' => $qcDate,
                        'qty_ok' => $qtyOk,
                        'qty_reject' => $qtyReject,
                        'operator_id' => $operatorId,
                        'status' => $this->decideBundleQcStatus($qtyOk, $qtyReject),
                        'notes' => $row['notes'] ?? null,
                    ]
                );

                // tentukan status bundle
                $bundleStatus = $this->decideBundleQcStatus($qtyOk, $qtyReject);

                // siapkan info WIP untuk bundle
                $wipWarehouseId = null;
                $wipQty = 0.0;

                if ($qtyOk > 0) {
                    $wipWarehouseId = $wipCutWarehouseId;
                    $wipQty = $qtyOk; // qty siap jahit
                }

                // update ringkasan di bundle (+ info WIP)
                $bundle->update([
                    'qty_qc_ok' => $qtyOk,
                    'qty_qc_reject' => $qtyReject,
                    'status' => $bundleStatus,
                    'wip_warehouse_id' => $wipWarehouseId,
                    'wip_qty' => $wipQty,
                ]);

                // === POST INVENTORY untuk qty OK (WIP-CUT) ===
                // NOTE: diasumsikan 1x posting; kalau QC di-edit ulang,
                // stok bisa dobel. Kalau nanti perlu, kita ubah jadi pakai selisih.
                $this->postBundleToWipCut($job, $bundle, $qtyOk, $qcDate, $wipCutWarehouseId);

                $totalOk += $qtyOk;
                $totalReject += $qtyReject;
            }

            // update status header cutting job berdasar status semua bundle
            $this->updateJobStatusFromBundles($job);
        });
    }

    /**
     * Posting inventory per bundle:
     * - Stock OUT dari gudang RM (LOT kain)
     * - Stock IN ke gudang WIP-CUT (item hasil cutting / WIP)
     *
     * Qty = qtyOk (hasil OK QC Cutting)
     */
    protected function postBundleToWipCut(
        CuttingJob $job,
        CuttingJobBundle $bundle,
        float $qtyOk,
        string $qcDate,
        int $wipCutWarehouseId,
    ): void {
        if ($qtyOk <= 0) {
            return;
        }

        // gudang asal = gudang RM yang dipakai Cutting Job
        $rmWarehouseId = $job->warehouse_id;

        if (!$bundle->lot) {
            throw new \RuntimeException("Bundle {$bundle->id} tidak memiliki LOT.");
        }

        // ============================
        // 1) STOCK OUT dari RM (LOT)
        // ============================
        $this->inventory->stockOut(
            warehouseId: $rmWarehouseId,
            itemId: $bundle->lot->item_id, // kain mentah
            qty: $qtyOk,
            date: $qcDate,
            sourceType: 'cutting_qc_out',
            sourceId: $job->id,
            notes: "QC Cutting OUT RM untuk bundle {$bundle->bundle_code} (job {$job->code})",
            allowNegative: false,
            lotId: $bundle->lot_id,
        );

        // ============================
        // 2) STOCK IN ke WIP-CUT
        // ============================
        $this->inventory->stockIn(
            warehouseId: $wipCutWarehouseId,
            itemId: $bundle->finished_item_id, // barang WIP hasil cutting
            qty: $qtyOk,
            date: $qcDate,
            sourceType: 'cutting_qc_in',
            sourceId: $job->id,
            notes: "QC Cutting IN WIP-CUT untuk bundle {$bundle->bundle_code} (job {$job->code})",
            lotId: $bundle->lot_id, // tetap pakai LOT kain untuk traceability cost
            unitCost: null, // null => InventoryService TIDAK mencatat cost baru (pakai avg LOT existing)
        );
    }

    /**
     * Tentukan status bundle berdasarkan qty OK & Reject.
     */
    protected function decideBundleQcStatus(float $qtyOk, float $qtyReject): string
    {
        if ($qtyReject <= 0 && $qtyOk > 0) {
            return 'qc_ok';
        }

        if ($qtyOk > 0 && $qtyReject > 0) {
            return 'qc_mixed';
        }

        if ($qtyOk <= 0 && $qtyReject > 0) {
            return 'qc_reject';
        }

        // tidak ada data QC yang bermakna -> balik ke status awal
        return 'cut';
    }

    /**
     * Update status header CuttingJob berdasarkan status bundle-bundle.
     */
    protected function updateJobStatusFromBundles(CuttingJob $job): void
    {
        $job->loadMissing('bundles');

        $bundles = $job->bundles;
        if ($bundles->isEmpty()) {
            return;
        }

        $allOk = $bundles->every(fn($b) => $b->status === 'qc_ok');
        $allReject = $bundles->every(fn($b) => $b->status === 'qc_reject');

        if ($allOk) {
            $status = 'qc_ok';
        } elseif ($allReject) {
            $status = 'qc_reject';
        } else {
            $status = 'qc_mixed';
        }

        // kalau sebelumnya statusnya cut_sent_to_qc, boleh diganti ke hasil qc
        $job->update([
            'status' => $status,
        ]);
    }

    /**
     * Normalisasi angka (format Indonesia juga).
     */
    protected function num(float | int | string | null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        // format Indonesia: 1.234,56
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value); // buang titik ribuan
            $value = str_replace(',', '.', $value); // koma -> titik
        }

        return (float) $value;
    }
}
