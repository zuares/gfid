<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutting_job_bundles', function (Blueprint $table) {
            $table->decimal('qty_qc_ok', 12, 2)
                ->default(0)
                ->after('qty_pcs');

            $table->decimal('qty_qc_reject', 12, 2)
                ->default(0)
                ->after('qty_qc_ok');
        });
    }

    public function down(): void
    {
        Schema::table('cutting_job_bundles', function (Blueprint $table) {
            $table->dropColumn(['qty_qc_ok', 'qty_qc_reject']);
        });
    }
};
