<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutting_job_bundles', function (Blueprint $table) {
            // setelah finished_item_id biar rapih
            $table->foreignId('item_category_id')
                ->nullable()
                ->after('finished_item_id')
                ->constrained('item_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cutting_job_bundles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('item_category_id');
        });
    }
};
