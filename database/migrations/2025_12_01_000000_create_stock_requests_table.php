<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_requests', function (Blueprint $table) {
            $table->id();

            // Kode dokumen — SR-20251201-001
            $table->string('code')->unique();

            // Tanggal permintaan
            $table->date('date');

            // Tujuan dokumen: rts_replenish, production_request, dll.
            $table->string('purpose')->default('rts_replenish');

            // Gudang asal & tujuan
            $table->foreignId('source_warehouse_id')->constrained('warehouses');
            $table->foreignId('destination_warehouse_id')->constrained('warehouses');

            // Status workflow
            // draft → submitted → completed (+ optional partial)
            $table->string('status')->default('draft');

            // siapa yang request
            $table->foreignId('requested_by_user_id')
                ->nullable()
                ->constrained('users');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_requests');
    }
};
