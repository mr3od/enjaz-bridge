<?php

namespace App\Support\Media;

use App\Support\Tenancy\AgencyScopeResolver;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class AgencyPathGenerator implements PathGenerator
{
    public function __construct(private AgencyScopeResolver $agencyScopeResolver) {}

    public function getPath(Media $media): string
    {
        return $this->basePath($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->basePath($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->basePath($media).'/responsive/';
    }

    private function basePath(Media $media): string
    {
        $model = $media->model;

        if (! $model instanceof Model) {
            return "unscoped/media/{$media->id}";
        }

        $agencyId = $this->agencyScopeResolver->resolve($model);

        if ($agencyId === null) {
            return "unscoped/media/{$media->id}";
        }

        return "agencies/{$agencyId}/{$model->getTable()}/{$model->getKey()}/{$media->id}";
    }
}
