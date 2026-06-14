<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sync_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_id')->nullable()->constrained('tahun')->nullOnDelete();
            $table->foreignId('opd_id')->nullable()->constrained('opd')->nullOnDelete();
            $table->string('document_type')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->integer('current_step')->default(0);
            $table->integer('total_steps')->default(0);
            $table->text('current_message')->nullable();
            $table->json('results')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_progress');
    }
};
