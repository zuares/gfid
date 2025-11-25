<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_wip_to_cutting_job_bundles.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutting_job_bundles', function (Blueprint $table) {
            $table->foreignId('wip_warehouse_id')
                ->nullable()
                ->after('operator_id')
                ->constrained('warehouses');

            $table->decimal('wip_qty', 12, 2)
                ->nullable()
                ->default(0)
                ->after('qty_qc_ok'); // kalau belum ada, taruh setelah qty_pcs saja
        });
    }

    public function down(): void
    {
        Schema::table('cutting_job_bundles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wip_warehouse_id');
            $table->dropColumn('wip_qty');
        });
    }
};
