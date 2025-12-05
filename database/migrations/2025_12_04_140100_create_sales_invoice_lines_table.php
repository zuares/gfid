<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoice_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_invoice_id')
                ->constrained('sales_invoices')
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('qty')->default(0);

            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('line_discount', 18, 2)->default(0);

            $table->decimal('line_total', 18, 2)->default(0);

            // nanti akan dipakai:
            $table->decimal('hpp_unit_snapshot', 18, 2)->default(0);
            $table->decimal('margin_unit', 18, 2)->default(0);
            $table->decimal('margin_total', 18, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_lines');
    }
};
