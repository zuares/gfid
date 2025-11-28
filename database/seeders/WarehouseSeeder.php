<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [

            // === RAW MATERIAL ===
            [
                'code' => 'RM',
                'name' => 'Raw Material Warehouse',
                'type' => 'raw_material',
            ],

            // === WIP ===
            [
                'code' => 'WIP-CUT',
                'name' => 'WIP Cutting Warehouse',
                'type' => 'wip',
            ],
            [
                'code' => 'WIP-SEW',
                'name' => 'WIP Sewing Warehouse',
                'type' => 'wip',
            ],
            [
                'code' => 'WIP-FIN',
                'name' => 'WIP Finishing Warehouse',
                'type' => 'wip',
            ],
            [
                'code' => 'WIP-PACK',
                'name' => 'WIP Packing Warehouse',
                'type' => 'wip',
            ],

            // === FINISHED GOODS (Baru selesai Finishing) ===
            [
                'code' => 'FG',
                'name' => 'Finished Goods Warehouse',
                'type' => 'fg',
            ],

            // === GUDANG BARU ===

            // Barang sudah packing – siap dijual
            [
                'code' => 'WH-RTS',
                'name' => 'Warehouse Ready To Sell',
                'type' => 'ready_to_sell',
            ],

            // Barang hasil produksi – belum dipindah ke WH-RTS
            [
                'code' => 'WH-PRD',
                'name' => 'Warehouse Production',
                'type' => 'production',
            ],

            // === REJECT FLOW ===
            [
                'code' => 'REJ-CUT',
                'name' => 'Reject Cutting',
                'type' => 'reject',
            ],
            [
                'code' => 'REJ-SEW',
                'name' => 'Reject Sewing',
                'type' => 'reject',
            ],
            [
                'code' => 'REJ-FIN',
                'name' => 'Reject Finishing',
                'type' => 'reject',
            ],
            [
                'code' => 'REJECT',
                'name' => 'General Reject / Defect Warehouse',
                'type' => 'reject',
            ],

            // === OPTIONAL ===
            [
                'code' => 'SAMPLE',
                'name' => 'Sample / Content Warehouse',
                'type' => 'internal',
            ],
            [
                'code' => 'SCRAP',
                'name' => 'Scrap / Waste Warehouse',
                'type' => 'internal',
            ],
        ];

        foreach ($warehouses as $wh) {
            Warehouse::updateOrCreate(
                ['code' => $wh['code']],
                [
                    'name' => $wh['name'],
                    'type' => $wh['type'],
                    'active' => 1,
                ]
            );
        }
    }
}
