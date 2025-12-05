<?php

namespace Database\Seeders;

use App\Models\Channel;
use Illuminate\Database\Seeder;

class ChannelsSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            ['code' => 'SHP', 'name' => 'Shopee'],
            ['code' => 'TTK', 'name' => 'Tiktok'],
            ['code' => 'OFFL', 'name' => 'Offline'],
        ];

        foreach ($channels as $c) {
            Channel::updateOrCreate(
                ['code' => $c['code']],
                [
                    'name' => $c['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
