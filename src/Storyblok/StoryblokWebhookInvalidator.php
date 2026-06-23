<?php

namespace TAFER\Core\Storyblok;

use Storyblok\Api\Domain\Value\Dto\Version;
use TAFER\Core\Contracts\StoryblokCacheInvalidator;
use TAFER\Core\Enums\Locale;

final readonly class StoryblokWebhookInvalidator
{
    public function __construct(
        private StoryblokCacheInvalidator $cache,
        private StoryblokSlugNormalizer $normalizer,
        private string $cacheNamespace = 'default',
    ) {}

    public function invalidate(
        string $fullSlug,
        ?Locale $locale = null,
        ?string $uuid = null,
    ): StoryblokInvalidationResult {
        $locale ??= $this->normalizer->localeFromSlug($fullSlug);
        $identity = $this->normalizer
            ->fromSlug($fullSlug, $locale)
            ->withUuid($uuid);

        return $this->cache->invalidate(
            $identity,
            new StoryblokCacheContext(
                locale: $locale,
                version: Version::Published,
                namespace: $this->cacheNamespace,
            ),
        );
    }

    /**
     * @param  list<Locale>  $locales
     * @return array<string, StoryblokInvalidationResult>
     */
    public function invalidateLocales(string $fullSlug, array $locales): array
    {
        $results = [];

        foreach ($locales as $locale) {
            $results[$locale->value] = $this->invalidate($fullSlug, $locale);
        }

        return $results;
    }
}
