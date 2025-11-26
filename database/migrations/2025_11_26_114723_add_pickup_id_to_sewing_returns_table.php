<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_returns', function (Blueprint $table) {
            // kalau tabel masih kosong, boleh non-nullable:
            // $table->foreignId('pickup_id')->constrained('sewing_pickups');

            // versi lebih aman (kalau takut sudah ada data lama):
            $table->foreignId('pickup_id')
                ->nullable()
                ->constrained('sewing_pickups');
        });
    }

    public function down(): void
    {
        Schema::table('sewing_returns', function (Blueprint $table) {
            $table->dropForeign(['pickup_id']);
            $table->dropColumn('pickup_id');
        });
    }
};
