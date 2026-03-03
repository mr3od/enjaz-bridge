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
        Schema::create('extractions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('applicant_id')->constrained('applicants')->cascadeOnDelete();
            $table->foreignUlid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('model_used');
            $table->json('raw_response');
            $table->json('extracted_data');
            $table->json('corrections')->nullable();
            $table->unsignedInteger('processing_ms')->default(0);
            $table->timestamps();

            $table->index(['agency_id', 'created_at']);
            $table->index(['applicant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extractions');
    }
};
