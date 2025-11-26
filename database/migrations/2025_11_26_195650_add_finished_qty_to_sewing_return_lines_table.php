<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_finished_qty_to_sewing_return_lines_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_return_lines', function (Blueprint $table) {
            $table->unsignedInteger('finished_qty')
                ->default(0)
                ->after('ok_qty');
        });
    }

    public function down(): void
    {
        Schema::table('sewing_return_lines', function (Blueprint $table) {
            $table->dropColumn('finished_qty');
        });
    }
};
