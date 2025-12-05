<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')
                ->constrained('shipments')
                ->cascadeOnDelete();

            // optional link ke invoice line (biar gampang cek sisa)
            $table->foreignId('sales_invoice_line_id')
                ->nullable()
                ->constrained('sales_invoice_lines')
                ->nullOnDelete();

            $table->foreignId('item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('qty')->default(0); // qty dikirim

            // tempat catat hasil scan (optional)
            $table->string('scan_code')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_lines');
    }
};
