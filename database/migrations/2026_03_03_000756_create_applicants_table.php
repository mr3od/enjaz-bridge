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
        Schema::create('applicants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('passport_number')->nullable();
            $table->string('country_code', 3)->nullable();
            $table->text('mrz_line_1')->nullable();
            $table->text('mrz_line_2')->nullable();
            $table->string('surname_ar')->nullable();
            $table->string('given_names_ar')->nullable();
            $table->string('surname_en')->nullable();
            $table->string('given_names_en')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth_ar')->nullable();
            $table->string('place_of_birth_en')->nullable();
            $table->string('sex', 1)->nullable();
            $table->date('date_of_issue')->nullable();
            $table->date('date_of_expiry')->nullable();
            $table->string('profession_ar')->nullable();
            $table->string('profession_en')->nullable();
            $table->string('issuing_authority_ar')->nullable();
            $table->string('issuing_authority_en')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->string('enjaz_status', 20)->default('not_submitted')->index();
            $table->timestamp('extraction_requested_at')->nullable();
            $table->timestamp('extraction_started_at')->nullable();
            $table->timestamp('extraction_finished_at')->nullable();
            $table->text('extraction_error')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
