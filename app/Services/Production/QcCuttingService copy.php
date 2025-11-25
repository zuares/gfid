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
    ) {}

    /**
     * Simpan QC Cutting + apply inventory (sekali saja).
     *
     * @param  CuttingJob  $job
     * @param  array       $payload  (hasil $request->validate)
     */
    public function saveCuttingQc(CuttingJob $job, array $payload): CuttingJob
    {
        return DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $results = $payload['results'] ?? [];

            // simpan qty_ok per bundle_id untuk dipakai saat stockIn WIP-CUT
            $bundleQtyOk = [];

            foreach ($results as $row) {
                $bundleId = (int) ($row['bundle_id'] ?? 0);
                if (!$bundleId) {
                    continue;
                }

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $job->bundles()->whereKey($bundleId)->first();
                if (!$bundle) {
                    continue;
                }

                $qtyOk = $this->num($row['qty_ok'] ?? 0);
                $qtyReject = $this->num($row['qty_reject'] ?? 0);

                // jaga-jaga nilai negatif
                if ($qtyOk < 0) {
                    $qtyOk = 0;
                }
                if ($qtyReject < 0) {
                    $qtyReject = 0;
                }

                // batasi jangan lebih besar dari qty_pcs bundle
                if ($qtyOk > $bundle->qty_pcs) {
                    $qtyOk = $bundle->qty_pcs;
                }
                if ($qtyReject > $bundle->qty_pcs) {
                    $qtyReject = $bundle->qty_pcs;
                }

                // optional: pastikan qtyOk + qtyReject <= qty_pcs
                if ($qtyOk + $qtyReject > $bundle->qty_pcs) {
                    $qtyReject = max(0, $bundle->qty_pcs - $qtyOk);
                }

                // tentukan status QC per bundle
                $status = 'pending';
                if ($qtyOk > 0 && $qtyReject <= 0) {
                    $status = 'ok';
                } elseif ($qtyOk > 0 && $qtyReject > 0) {
                    $status = 'mixed';
                } elseif ($qtyOk <= 0 && $qtyReject > 0) {
                    $status = 'reject';
                }
                // upsert qc_results (stage = cutting)
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
                        'status' => $status,
                        'notes' => $row['notes'] ?? null,
                    ]
                );

                $bundleQtyOk[$bundle->id] = $qtyOk;
            }

            // Inventory hanya diaplikasikan SEKALI,
            // misalnya ketika status awal masih 'cut'
            if ($job->status === 'sent_to_qc') {
                $this->applyInventoryAfterQc($job, $qcDate, $bundleQtyOk);

                $job->update([
                    'status' => 'qc_done',
                ]);
            }

            // reload relasi QC biar fresh
            return $job->fresh([
                'bundles.qcResults' => function ($q) {
                    $q->where('stage', 'cutting');
                },
            ]);
        });
    }

    /**
     * Kurangi LOT di RM + buat stok WIP-CUT per bundle (qty_ok).
     *
     * - stockOut(RM, LOT kain, total pemakaian kain)
     * - stockIn(CUT, item hasil cutting per bundle, qty_ok)
     *
     * Moving average per LOT akan dikerjakan di dalam InventoryService
     * karena unitCost kita kirim null.
     */
    protected function applyInventoryAfterQc(
        CuttingJob $job,
        string | \DateTimeInterface $qcDate,
        array $bundleQtyOk
    ): void {
        // 1. total pemakaian kain dari semua bundle
        $qtyFabricUsed = $job->bundles()->sum('qty_used_fabric');
        if ($qtyFabricUsed <= 0) {
            return;
        }

        // 2. ambil gudang RM & CUT (sesuaikan kode kalau beda)
        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');
        $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id'); // anggap 'CUT' = WIP Cutting
        if (!$rmWarehouseId || !$wipCutWarehouseId) {
            // Bisa juga lempar exception kalau mau dipaksa benar
            return;
        }

        // 3. item kain sumber LOT
        $fabricItemId = $job->fabric_item_id ?? $job->lot?->item_id;
        if (!$fabricItemId) {
            return;
        }

        // 4. STOCK OUT kain dari gudang RM per LOT
        $this->inventory->stockOut(
            warehouseId: $rmWarehouseId,
            itemId: $fabricItemId,
            qty: $qtyFabricUsed,
            date: $qcDate,
            sourceType: 'cutting_qc',
            sourceId: $job->id,
            notes: "Pemakaian kain cutting job {$job->code}",
            lotId: $job->lot_id,
        );

        // 5. STOCK IN WIP-CUT per bundle (hanya qty_ok)
        foreach ($job->bundles as $bundle) {
            $qtyOk = $bundleQtyOk[$bundle->id] ?? 0;
            if ($qtyOk <= 0) {
                continue;
            }

            $this->inventory->stockIn(
                warehouseId: $wipCutWarehouseId,
                itemId: $bundle->finished_item_id, // barang setengah jadi hasil cutting
                qty: $qtyOk,
                date: $qcDate,
                sourceType: 'cutting_qc',
                sourceId: $job->id,
                notes: "WIP Cutting untuk bundle {$bundle->bundle_code} (job {$job->code})",
                lotId: null,
                unitCost: null, // << biar InventoryService pakai moving average LOT
            );
        }
    }

    /**
     * Normalisasi angka (mirip di CuttingService).
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

        // format Indonesia 1.234,56
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }
}
