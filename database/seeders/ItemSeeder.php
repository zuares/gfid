<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // ===================
            // BAHAN / MATERIAL
            // ===================
            [
                'code' => 'FLC280BLK',
                'name' => 'Fleece 280gsm Black',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => 1, // misal kategori bahan
            ],
            [
                'code' => 'FLC280NVY',
                'name' => 'Fleece 280gsm Navy',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => 1,
            ],
            [
                'code' => 'RIB280BLK',
                'name' => 'RIB Hitam',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => 1,
            ],
            [
                'code' => 'RIB280NVY',
                'name' => 'RIB Navy',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => 1,
            ],

            // ===================
            // BARANG JADI
            // ===================
            [
                'code' => 'K7BLK',
                'name' => 'Celana SJR Ukuran 7XL Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => 4, // kategori barang jadi
            ],
            [
                'code' => 'J7BLK',
                'name' => 'Celana LJR Ukuran 5 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => 5,
            ],
            [
                'code' => 'K7NVY',
                'name' => 'Celana SJR Ukuran 7XL Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => 4, // kategori barang jadi
            ],
            [
                'code' => 'J7NVY',
                'name' => 'Celana LJR Ukuran 5 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => 5,
            ],

            // ===================
            // AKSESORIS
            // ===================
            [
                'code' => 'KRT4CM',
                'name' => 'Karet Pinggang 30mm',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => 2,
            ],

            [
                'code' => 'TLR150',
                'name' => 'Karet Pinggang 4Cm',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => 2,
            ],
        ];

        foreach ($data as $row) {
            Item::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
