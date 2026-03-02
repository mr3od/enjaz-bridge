<?php

use Illuminate\Support\Facades\Schema;

test('agencies table and users agency foreign key exist', function () {
    expect(Schema::hasTable('agencies'))->toBeTrue();
    expect(Schema::hasColumns('agencies', [
        'id',
        'name',
        'slug',
        'city',
        'plan',
        'monthly_quota',
        'used_this_month',
        'quota_resets_at',
        'is_active',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumn('users', 'agency_id'))->toBeTrue();

    if (method_exists(Schema::getFacadeRoot(), 'getForeignKeys')) {
        $foreignKeys = Schema::getForeignKeys('users');
        $hasAgencyForeignKey = collect($foreignKeys)->contains(function (array $foreignKey): bool {
            return collect($foreignKey['columns'] ?? [])->contains('agency_id');
        });

        expect($hasAgencyForeignKey)->toBeTrue();
    }
});
