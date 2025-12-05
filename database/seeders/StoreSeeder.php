<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // Shopee
            ['name' => 'Insight', 'channel_code' => 'SHP'],

            // Tokopedia
            ['name' => 'Gfid', 'channel_code' => 'TTK'],

            // Offline
            ['name' => 'Offline', 'channel_code' => 'OFFL'],
            // Instagram
        ];

        foreach ($data as $row) {
            $channel = Channel::where('code', $row['channel_code'])->first();

            if (!$channel) {
                continue;
            }

            // bentuk code: SHP-SHOPEE-PUSAT, TKP-TOKOPEDIA-OFFICIAL, dll
            $code = strtoupper($channel->code . '-' . Str::slug($row['name'], '-'));

            Store::updateOrCreate(
                [
                    // key unik (biar ga dobel)
                    'code' => $code,
                ],
                [
                    'name' => $row['name'],
                    'channel_id' => $channel->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
