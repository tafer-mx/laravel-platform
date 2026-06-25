<?php

namespace TAFER\Core\Storyblok;

use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Api\Request\StoryRequest;

final readonly class StoryblokCachePolicy
{
    public function shouldCache(?StoryRequest $request, Version $defaultVersion): bool
    {
        $version = $request?->version ?? $defaultVersion;

        return $version === Version::Published;
    }
}
