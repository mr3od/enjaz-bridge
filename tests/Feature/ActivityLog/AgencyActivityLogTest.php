<?php

use App\Models\Activity;
use App\Models\Agency;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    app(TenantContext::class)->clear();
});

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

test('activity resolves agency from causer when no tenant context exists', function () {
    $user = User::factory()->create();

    activity()->causedBy($user)->log('queued-action');

    $log = Activity::query()
        ->where('description', 'queued-action')
        ->latest()
        ->firstOrFail();

    expect($log->agency_id)->toBe($user->agency_id);
});

test('activity resolves agency from subject when no causer or context exists', function () {
    $user = User::factory()->create();

    activity()->performedOn($user)->log('subject-action');

    $log = Activity::query()
        ->where('description', 'subject-action')
        ->latest()
        ->firstOrFail();

    expect($log->agency_id)->toBe($user->agency_id);
});

test('activity returns null agency for pure system logs', function () {
    activity()->log('system-boot');

    $log = Activity::query()
        ->where('description', 'system-boot')
        ->latest()
        ->firstOrFail();

    expect($log->agency_id)->toBeNull();
});

test('activity prefers explicit agency_id over fallback chain', function () {
    $explicitAgency = Agency::factory()->create();
    $causer = User::factory()->create();

    activity()
        ->causedBy($causer)
        ->tap(function (Activity $activity) use ($explicitAgency): void {
            $activity->agency_id = $explicitAgency->id;
        })
        ->log('explicit-agency');

    $log = Activity::query()
        ->where('description', 'explicit-agency')
        ->latest()
        ->firstOrFail();

    expect($log->agency_id)->toBe($explicitAgency->id);
});

test('activity prefers tenant context over causer agency', function () {
    $contextAgency = Agency::factory()->create();
    $causer = User::factory()->create();

    app(TenantContext::class)->setAgency($contextAgency);

    activity()->causedBy($causer)->log('context-over-causer');

    $log = Activity::query()
        ->where('description', 'context-over-causer')
        ->latest()
        ->firstOrFail();

    expect($log->agency_id)->toBe($contextAgency->id);
});
