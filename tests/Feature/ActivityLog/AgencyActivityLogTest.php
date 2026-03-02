<?php

use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('activity rows are tagged with agency_id', function () {
    $agency = Agency::query()->create([
        'name' => 'Log Agency',
        'slug' => 'log-agency',
    ]);

    $user = User::factory()->create([
        'agency_id' => $agency->id,
    ]);

    $this->actingAs($user);

    activity()->performedOn($user)->log('profile-updated');

    $exists = DB::table(config('activitylog.table_name'))
        ->where('description', 'profile-updated')
        ->where('agency_id', $agency->id)
        ->exists();

    expect($exists)->toBeTrue();
});

test('activity can be filtered by agency without tenant bleed', function () {
    $agencyA = Agency::query()->create([
        'name' => 'Agency A',
        'slug' => 'activity-agency-a',
    ]);

    $agencyB = Agency::query()->create([
        'name' => 'Agency B',
        'slug' => 'activity-agency-b',
    ]);

    $userA = User::factory()->create(['agency_id' => $agencyA->id]);
    $userB = User::factory()->create(['agency_id' => $agencyB->id]);

    $this->actingAs($userA);
    activity()->performedOn($userA)->log('agency-a-action');

    $this->actingAs($userB);
    activity()->performedOn($userB)->log('agency-b-action');

    $agencyARows = DB::table(config('activitylog.table_name'))
        ->where('agency_id', $agencyA->id)
        ->pluck('description')
        ->all();

    expect($agencyARows)->toContain('agency-a-action');
    expect($agencyARows)->not->toContain('agency-b-action');
});
