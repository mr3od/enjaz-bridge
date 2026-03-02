<?php

namespace App\Support\Media;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class AgencyPathGenerator implements PathGenerator
{
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

        $agencyId = $model->getAttribute('agency_id');

        if ($agencyId === null && tenancy()->initialized && tenancy()->tenant !== null) {
            $agencyId = tenancy()->tenant->getTenantKey();
        }

        if ($agencyId === null) {
            return "unscoped/media/{$media->id}";
        }

        return "agencies/{$agencyId}/{$model->getTable()}/{$model->getKey()}/{$media->id}";
    }
}
