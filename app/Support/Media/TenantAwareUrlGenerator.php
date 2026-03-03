<?php

declare(strict_types=1);

namespace App\Support\Media;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class TenantAwareUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        $diskName = $this->media->disk;
        $path = $this->getPathRelativeToRoot();
        $lifetimeMinutes = max(
            60,
            (int) config('media-library.temporary_url_default_lifetime', 5)
        );

        $url = Storage::disk($diskName)->temporaryUrl(
            $path,
            now()->addMinutes($lifetimeMinutes)
        );

        $url = $this->versionUrl($url);

        return $url;
    }
}
