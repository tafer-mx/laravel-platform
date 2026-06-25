<?php

namespace TAFER\Core\Contracts;

use TAFER\Core\Storyblok\StoryblokCacheContext;
use TAFER\Core\Storyblok\StoryblokIdentity;
use TAFER\Core\Storyblok\StoryblokInvalidationResult;

interface StoryblokCacheInvalidator
{
    public function invalidate(
        StoryblokIdentity $identity,
        StoryblokCacheContext $context,
    ): StoryblokInvalidationResult;

    public function invalidateByUuid(
        string $uuid,
        StoryblokCacheContext $context,
    ): StoryblokInvalidationResult;
}
