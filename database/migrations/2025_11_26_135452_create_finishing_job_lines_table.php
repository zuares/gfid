<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finishing_job_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('finishing_job_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // PENTING: rujuk ke cutting_job_bundles (bukan cutting_bundles)
            $table->foreignId('bundle_id')
                ->constrained('cutting_job_bundles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // operator finishing (boleh nullable)
            $table->foreignId('operator_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('qty_in', 12, 2);
            $table->decimal('qty_ok', 12, 2);
            $table->decimal('qty_reject', 12, 2)->default(0);

            $table->string('reject_reason')->nullable();
            $table->text('reject_notes')->nullable();

            $table->date('processed_at')->nullable();

            $table->timestamps();

            $table->index(['bundle_id', 'item_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('finishing_job_lines');
    }
};
