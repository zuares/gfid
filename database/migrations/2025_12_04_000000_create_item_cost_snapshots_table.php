<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_cost_snapshots', function (Blueprint $table) {
            $table->id();

            // Item selesai (Finished Good)
            $table->unsignedBigInteger('item_id');

            // Gudang opsional (misal WH-RTS)
            $table->unsignedBigInteger('warehouse_id')->nullable();

            // Tanggal snapshot
            $table->date('snapshot_date');

            // Referensi pembuat snapshot
            $table->string('reference_type')->nullable(); // auto_hpp_period, manual_adjustment, dll
            $table->unsignedBigInteger('reference_id')->nullable();

            // Qty basis HPP
            $table->decimal('qty_basis', 14, 4)->default(0);

            // Komponen biaya
            $table->decimal('rm_unit_cost', 14, 4)->default(0);
            $table->decimal('cutting_unit_cost', 14, 4)->default(0);
            $table->decimal('sewing_unit_cost', 14, 4)->default(0);
            $table->decimal('finishing_unit_cost', 14, 4)->default(0);
            $table->decimal('packaging_unit_cost', 14, 4)->default(0);
            $table->decimal('overhead_unit_cost', 14, 4)->default(0);

            // Total HPP final
            $table->decimal('unit_cost', 14, 4)->default(0);

            // Catatan
            $table->string('notes')->nullable();

            // Active snapshot
            $table->boolean('is_active')->default(false);

            // Creator
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Index penting
            $table->index(['item_id', 'warehouse_id']);
            $table->index(['item_id', 'warehouse_id', 'is_active'], 'item_cost_snap_active_idx');

            // Foreign keys (optional)
            // $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            // $table->foreign('warehouse_id')->references('id')->on('warehouses');
            // $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_cost_snapshots');
    }
};
