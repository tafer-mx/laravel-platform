<?php

namespace TAFER\Core\Storyblok;

use Storyblok\Api\Domain\Value\Dto\Version;
use TAFER\Core\Contracts\StoryblokCacheInvalidator;
use TAFER\Core\Enums\Locale;

final readonly class StoryblokWebhookInvalidator
{
    public function __construct(
        private StoryblokCacheInvalidator $cache,
        private string $cacheNamespace = 'default',
    ) {}

    public function invalidate(
        string $fullSlug,
        ?Locale $locale = null,
        ?string $uuid = null,
    ): StoryblokInvalidationResult {
        $locale ??= $this->localeFromSlug($fullSlug);
        $identity = new StoryblokIdentity(
            canonicalSlug: $this->canonicalSlug($fullSlug),
            locale: $locale,
            uuid: $uuid,
        );

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

    private function localeFromSlug(string $slug, Locale $fallback = Locale::English): Locale
    {
        $firstSegment = explode('/', trim($slug, '/'))[0] ?? '';

        return Locale::tryFrom($firstSegment) ?? $fallback;
    }

    private function canonicalSlug(string $slug): string
    {
        $segments = array_values(array_filter(
            explode('/', trim($slug, '/')),
            static fn (string $segment): bool => $segment !== '',
        ));

        if ($segments !== [] && Locale::tryFrom($segments[0]) !== null) {
            array_shift($segments);
        }

        return implode('/', $segments);
    }
}
