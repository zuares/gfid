<?php

namespace Database\Seeders;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Lot; // ⬅️ TAMBAH INI
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoCuttingPayrollSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();

        // 1. EMPLOYEE (operator cutting)
        $mrf = Employee::firstOrCreate(
            ['code' => 'MRF'],
            [
                'name' => 'Operator Cutting MRF',
                'default_piece_rate' => 400,
            ]
        );

        // 2. ITEM CATEGORY
        $kaosCategory = ItemCategory::firstOrCreate(
            ['code' => 'KAOS'],
            [
                'name' => 'Kaos Oblong',
            ]
        );

        // 3. ITEMS
        $itemK7BLK = Item::firstOrCreate(
            ['code' => 'K7BLK'],
            [
                'name' => 'Kaos Hitam Lengan Pendek',
                'item_category_id' => $kaosCategory->id,
                'type' => 'finished_good',
                // HAPUS is_active kalau kolomnya nggak ada
                // 'is_active' => true,
            ]
        );

        $itemK7WHT = Item::firstOrCreate(
            ['code' => 'K7WHT'],
            [
                'name' => 'Kaos Putih Lengan Pendek',
                'item_category_id' => $kaosCategory->id,
                'type' => 'finished_good',
                // 'is_active' => true,
            ]
        );

        // 4. WAREHOUSE untuk cutting job (WAJIB karena warehouse_id NOT NULL)
        // Sesuaikan: kalau kamu sudah punya warehouse kode 'CUT' atau 'G-CUT', pakai itu
        $cuttingWarehouse = Warehouse::firstOrCreate(
            ['code' => 'CUT-DEMO'], // bebas, yang penting ada id-nya
            [
                'name' => 'Gudang Cutting Demo',
                'type' => 'cutting', // kalau tabelmu punya kolom type, kalau tidak, hapus
            ]
        );

        // 5. CUTTING JOB (header)
        $cuttingJob = CuttingJob::firstOrCreate(
            ['code' => 'CUT-DEMO-001'],
            [
                'date' => $today->copy()->subDay()->toDateString(), // kemarin
                'status' => 'done',
                'operator_id' => $mrf->id,
                'warehouse_id' => $cuttingWarehouse->id,
                'lot_id' => 1,
                // 'item_category_id' => 3,
                // ⬅️ INI YANG WAJIB
                // kalau cutting_jobs punya kolom lain yang NOT NULL (lot_id, lot_code, dsb)
                // tambahkan juga di sini
            ]
        );

        // 6. CUTTING JOB BUNDLES
        CuttingJobBundle::firstOrCreate(
            [
                'cutting_job_id' => $cuttingJob->id,
                'bundle_no' => 1,
            ],
            [
                'bundle_code' => 'BND-DEMO-001-01',
                'lot_id' => null,
                'finished_item_id' => $itemK7BLK->id,
                'item_category_id' => $kaosCategory->id,
                'qty_pcs' => 50,
                'qty_used_fabric' => 0,
                'operator_id' => $mrf->id,
                'status' => 'qc_done',
                'notes' => 'Demo bundle K7BLK',
                'qty_qc_ok' => 50,
                'qty_qc_reject' => 0,
                'wip_warehouse_id' => null,
                'wip_qty' => 0,
            ]
        );

        CuttingJobBundle::firstOrCreate(
            [
                'cutting_job_id' => $cuttingJob->id,
                'bundle_no' => 2,
            ],
            [
                'bundle_code' => 'BND-DEMO-001-02',
                'lot_id' => null,
                'finished_item_id' => $itemK7WHT->id,
                'item_category_id' => $kaosCategory->id,
                'qty_pcs' => 30,
                'qty_used_fabric' => 0,
                'operator_id' => $mrf->id,
                'status' => 'qc_done',
                'notes' => 'Demo bundle K7WHT',
                'qty_qc_ok' => 30,
                'qty_qc_reject' => 0,
                'wip_warehouse_id' => null,
                'wip_qty' => 0,
            ]
        );
    }
}
