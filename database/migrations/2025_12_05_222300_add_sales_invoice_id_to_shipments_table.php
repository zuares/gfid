<?php
// database/migrations/xxxx_xx_xx_add_sales_invoice_id_to_shipments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_invoice_id')
                ->nullable()
                ->after('store_id');

            $table->foreign('sales_invoice_id')
                ->references('id')
                ->on('sales_invoices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['sales_invoice_id']);
            $table->dropColumn('sales_invoice_id');
        });
    }
};
