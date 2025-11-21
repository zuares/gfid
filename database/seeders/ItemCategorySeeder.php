<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use Illuminate\Database\Seeder;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // ============================
            // KATEGORI BAHAN MENTAH
            // ============================
            [
                'code' => 'MAT',
                'name' => 'Material / Bahan Baku',
            ],
            [
                'code' => 'ACC',
                'name' => 'Accessories',
            ],

            // ============================
            // KATEGORI BARANG JADI
            // ============================
            [
                'code' => 'FG',
                'name' => 'Finished Goods',
            ],

            // ============================
            // KATEGORI PRODUKSI (HPP / Payroll)
            // ============================
            [
                'code' => 'SJR',
                'name' => 'Celana Jogger Pendek Basic',
            ],
            [
                'code' => 'LJR',
                'name' => 'Celana Jogger Panjang',
            ],
            [
                'code' => 'HDY',
                'name' => 'Hoodie / Sweater',
            ],
            [
                'code' => 'TSH',
                'name' => 'T-shirt / Kaos',
            ],
        ];

        foreach ($data as $row) {
            ItemCategory::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
