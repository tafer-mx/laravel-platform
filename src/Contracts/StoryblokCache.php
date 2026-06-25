<?php

namespace TAFER\Core\Contracts;

use TAFER\Core\Storyblok\CachedStory;
use TAFER\Core\Storyblok\StoryblokCacheContext;
use TAFER\Core\Storyblok\StoryblokIdentity;

interface StoryblokCache extends StoryblokCacheInvalidator
{
    public function get(StoryblokIdentity $identity, StoryblokCacheContext $context): ?CachedStory;

    public function getByUuid(string $uuid, StoryblokCacheContext $context): ?CachedStory;

    public function has(StoryblokIdentity $identity, StoryblokCacheContext $context): bool;

    public function put(
        StoryblokIdentity $identity,
        CachedStory $story,
        StoryblokCacheContext $context,
        bool $isRelation = false,
    ): void;
}
