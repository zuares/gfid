<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();

            // Kode LOT, misal: LOT-20251121-001
            $table->string('code')->unique();

            // Optional: kaitkan ke item (bahan)
            $table->foreignId('item_id')
                ->nullable()
                ->constrained('items')
                ->nullOnDelete();

            // Qty dan unit (optional, bisa kamu pakai nanti di modul produksi)
            $table->decimal('qty', 15, 3)->default(0);
            $table->string('unit', 10)->default('pcs');

            // Status LOT (raw / wip / used / etc), sementara default 'raw'
            $table->string('status', 20)->default('raw');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
