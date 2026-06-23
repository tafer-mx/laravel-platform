<?php

namespace TAFER\Core\Storyblok;

use InvalidArgumentException;
use TAFER\Core\Enums\Locale;

final class StoryblokSlugNormalizer
{
    public function fromSlug(string $slug, Locale $locale): StoryblokIdentity
    {
        return new StoryblokIdentity(
            canonicalSlug: $this->canonicalSlug($slug),
            locale: $locale,
        );
    }

    /**
     * @param  array<string, mixed>  $story
     */
    public function fromStory(array $story, Locale $locale): StoryblokIdentity
    {
        $fullSlug = $story['full_slug'] ?? null;

        if (! is_string($fullSlug) || trim($fullSlug, '/') === '') {
            throw new InvalidArgumentException('The Storyblok story must contain a non-empty full_slug.');
        }

        $uuid = $story['uuid'] ?? null;

        return new StoryblokIdentity(
            canonicalSlug: $this->canonicalSlug($fullSlug),
            locale: $locale,
            uuid: is_string($uuid) && $uuid !== '' ? $uuid : null,
        );
    }

    public function localeFromSlug(string $slug, Locale $fallback = Locale::English): Locale
    {
        $firstSegment = explode('/', trim($slug, '/'))[0] ?? '';

        return Locale::tryFrom($firstSegment) ?? $fallback;
    }

    public function canonicalSlug(string $slug): string
    {
        $segments = array_values(array_filter(
            explode('/', trim($slug)),
            static fn (string $segment): bool => $segment !== '',
        ));

        if ($segments !== [] && Locale::tryFrom($segments[0]) !== null) {
            array_shift($segments);
        }

        return implode('/', $segments);
    }
}
