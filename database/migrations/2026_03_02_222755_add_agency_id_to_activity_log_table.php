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
        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->foreignUlid('agency_id')
                ->nullable()
                ->after('causer_id')
                ->constrained('agencies')
                ->nullOnDelete();
            $table->index('agency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropColumn('agency_id');
        });
    }
};
