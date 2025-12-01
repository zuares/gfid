<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_request_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_request_id')
                ->constrained('stock_requests')
                ->onDelete('cascade');

            // Urutan baris
            $table->unsignedInteger('line_no')->default(1);

            // Item FG atau item lain sesuai purpose
            $table->foreignId('item_id')->constrained('items');

            // qty yang diminta
            $table->decimal('qty_request', 15, 2);

            // stok gudang saat user membuat request
            $table->decimal('stock_snapshot_at_request', 15, 2)->default(0);

            // qty yang benar-benar dikirim PRD
            // (untuk proses gudang)
            $table->decimal('qty_issued', 15, 2)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_request_lines');
    }
};
