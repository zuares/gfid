<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'code' => 'TPL',
                'name' => 'TOPLIS JAYA',
                'phone' => '081234567890',
                'address' => 'Palembang, Sumatera Selatan',
            ],
            [
                'code' => 'ORG',
                'name' => 'ORIGAMI TEXTILE',
                'phone' => '081298765432',
                'address' => 'Bandung, Jawa Barat',
            ],
        ];

        foreach ($data as $row) {
            Supplier::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
