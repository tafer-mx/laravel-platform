<?php

namespace TAFER\Core\Storyblok;

use Illuminate\Contracts\Cache\Repository;
use TAFER\Core\Contracts\StoryblokCache;

final readonly class LaravelStoryblokCache implements StoryblokCache
{
    public function __construct(
        private Repository $cache,
        private StoryblokCacheKey $keys,
        private ?int $storyTtl = null,
        private ?int $relationTtl = null,
    ) {}

    public function get(StoryblokIdentity $identity, StoryblokCacheContext $context): ?CachedStory
    {
        $payload = $this->cache->get($this->keys->payload($identity, $context));

        return is_array($payload) ? CachedStory::fromArray($payload) : null;
    }

    public function getByUuid(string $uuid, StoryblokCacheContext $context): ?CachedStory
    {
        $uuidKey = $this->keys->uuid($uuid, $context);
        $slug = $this->cache->get($uuidKey);

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        $identity = new StoryblokIdentity($slug, $context->locale, $uuid);
        $story = $this->get($identity, $context);

        if ($story !== null) {
            return $story;
        }

        $this->cache->forget($uuidKey);
        $this->cache->forget($this->keys->slugUuid($identity, $context));

        return null;
    }

    public function has(StoryblokIdentity $identity, StoryblokCacheContext $context): bool
    {
        return $this->cache->has($this->keys->payload($identity, $context));
    }

    public function put(
        StoryblokIdentity $identity,
        CachedStory $story,
        StoryblokCacheContext $context,
        StoryblokCacheEntry $entry = StoryblokCacheEntry::Story,
    ): void {
        $uuid = $identity->uuid ?? $story->story['uuid'] ?? null;
        $identity = $identity->withUuid(is_string($uuid) && $uuid !== '' ? $uuid : null);
        $ttl = $entry === StoryblokCacheEntry::Relation
            ? $this->relationTtl
            : $this->storyTtl;

        $this->store(
            $this->keys->payload($identity, $context),
            $story->toArray(),
            $ttl,
        );

        if ($identity->uuid === null) {
            return;
        }

        $this->store(
            $this->keys->uuid($identity->uuid, $context),
            $identity->canonicalSlug,
            $ttl,
        );
        $this->store(
            $this->keys->slugUuid($identity, $context),
            $identity->uuid,
            $ttl,
        );
    }

    public function invalidate(
        StoryblokIdentity $identity,
        StoryblokCacheContext $context,
    ): StoryblokInvalidationResult {
        $payloadKey = $this->keys->payload($identity, $context);
        $slugIndexKey = $this->keys->slugUuid($identity, $context);
        $uuid = $identity->uuid ?? $this->cache->get($slugIndexKey);
        $uuidKey = is_string($uuid) && $uuid !== ''
            ? $this->keys->uuid($uuid, $context)
            : null;

        $payloadExisted = $this->cache->has($payloadKey);
        $slugIndexExisted = $this->cache->has($slugIndexKey);
        $uuidIndexExisted = $uuidKey !== null && $this->cache->has($uuidKey);

        return new StoryblokInvalidationResult(
            payloadExisted: $payloadExisted,
            payloadForgotten: ! $payloadExisted || $this->cache->forget($payloadKey),
            uuidIndexExisted: $uuidIndexExisted,
            uuidIndexForgotten: ! $uuidIndexExisted || $this->cache->forget($uuidKey),
            slugIndexExisted: $slugIndexExisted,
            slugIndexForgotten: ! $slugIndexExisted || $this->cache->forget($slugIndexKey),
        );
    }

    public function invalidateByUuid(
        string $uuid,
        StoryblokCacheContext $context,
    ): StoryblokInvalidationResult {
        $uuidKey = $this->keys->uuid($uuid, $context);
        $slug = $this->cache->get($uuidKey);

        if (! is_string($slug) || $slug === '') {
            $existed = $this->cache->has($uuidKey);

            return new StoryblokInvalidationResult(
                payloadExisted: false,
                payloadForgotten: true,
                uuidIndexExisted: $existed,
                uuidIndexForgotten: ! $existed || $this->cache->forget($uuidKey),
                slugIndexExisted: false,
                slugIndexForgotten: true,
            );
        }

        return $this->invalidate(
            new StoryblokIdentity($slug, $context->locale, $uuid),
            $context,
        );
    }

    private function store(string $key, mixed $value, ?int $ttl): void
    {
        if ($ttl === null) {
            $this->cache->forever($key, $value);

            return;
        }

        $this->cache->put($key, $value, $ttl);
    }
}
