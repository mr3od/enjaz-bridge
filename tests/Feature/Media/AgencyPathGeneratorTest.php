<?php

use App\Models\Agency;
use App\Models\User;
use App\Support\Media\AgencyPathGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

test('media paths are scoped by agency', function () {
    $agency = Agency::query()->create([
        'name' => 'Media Agency',
        'slug' => 'media-agency',
    ]);

    $user = User::factory()->create([
        'agency_id' => $agency->id,
    ]);

    $media = new Media;
    $media->id = 42;
    $media->setRelation('model', $user);

    $generator = app(AgencyPathGenerator::class);

    expect($generator->getPath($media))->toBe("agencies/{$agency->id}/users/{$user->id}/42/");
    expect($generator->getPathForConversions($media))->toBe("agencies/{$agency->id}/users/{$user->id}/42/conversions/");
    expect($generator->getPathForResponsiveImages($media))->toBe("agencies/{$agency->id}/users/{$user->id}/42/responsive/");
});

test('media paths fallback to unscoped when no agency context exists', function () {
    $media = new Media;
    $media->id = 77;

    $generator = app(AgencyPathGenerator::class);

    expect($generator->getPath($media))->toBe('unscoped/media/77/');
    expect($generator->getPathForConversions($media))->toBe('unscoped/media/77/conversions/');
    expect($generator->getPathForResponsiveImages($media))->toBe('unscoped/media/77/responsive/');
});
