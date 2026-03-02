<?php

use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

test('registering user gets owner role in their agency scope', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Role Owner',
        'phone' => '+967712345679',
        'agency_name' => 'Role Agency',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('phone', '+967712345679')->firstOrFail();

    app(PermissionRegistrar::class)->setPermissionsTeamId($user->agency_id);

    expect($user->hasRole('owner'))->toBeTrue();
});
