<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_sewing_picked_qty_to_cutting_job_bundles.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutting_job_bundles', function (Blueprint $table) {
            $table->decimal('sewing_picked_qty', 12, 2)
                ->default(0)
                ->after('qty_pcs'); // atur posisi sesuai kebutuhan
        });
    }

    public function down(): void
    {
        Schema::table('cutting_job_bundles', function (Blueprint $table) {
            $table->dropColumn('sewing_picked_qty');
        });
    }
};
