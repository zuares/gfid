<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['code' => 'OFFL', 'name' => 'Offline Store', 'channel' => 'offline'],
            ['code' => 'SHP', 'name' => 'Shopee', 'channel' => 'marketplace'],
            ['code' => 'TKP', 'name' => 'Tokopedia', 'channel' => 'marketplace'],
            ['code' => 'IG', 'name' => 'Instagram', 'channel' => 'social'],
        ];

        foreach ($data as $row) {
            Store::updateOrCreate(
                ['code' => $row['code']],
                $row
            );
        }
    }
}
