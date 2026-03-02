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
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'agency_id';

        Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamForeignKey) {
            if (! Schema::hasColumn(config('permission.table_names.roles'), $teamForeignKey)) {
                $table->string($teamForeignKey, 26)->nullable()->after('id');
                $table->index($teamForeignKey, 'roles_team_foreign_key_index');
            } else {
                $table->string($teamForeignKey, 26)->nullable()->change();
            }
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey) {
            if (! Schema::hasColumn(config('permission.table_names.model_has_roles'), $teamForeignKey)) {
                $table->string($teamForeignKey, 26)->nullable();
                $table->index($teamForeignKey, 'model_has_roles_team_foreign_key_index');
            } else {
                $table->string($teamForeignKey, 26)->nullable()->change();
            }
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey) {
            if (! Schema::hasColumn(config('permission.table_names.model_has_permissions'), $teamForeignKey)) {
                $table->string($teamForeignKey, 26)->nullable();
                $table->index($teamForeignKey, 'model_has_permissions_team_foreign_key_index');
            } else {
                $table->string($teamForeignKey, 26)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $tableNames = config('permission.table_names');
        $teamForeignKey = config('permission.column_names.team_foreign_key');

        Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamForeignKey) {
            $table->unsignedBigInteger($teamForeignKey)->nullable()->change();
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey) {
            $table->unsignedBigInteger($teamForeignKey)->nullable()->change();
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey) {
            $table->unsignedBigInteger($teamForeignKey)->nullable()->change();
        });
    }
};
