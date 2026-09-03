<?php

namespace TAFER\Core\Storyblok;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Storyblok\Api\Domain\Value\Uuid;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Contracts\StoryblokGateway;
use TAFER\Core\Middlewares\ResolveRequestCtx;
use TAFER\Core\Services\StoryblokContextResolver;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
/**
 * Helper to resolve Storyblok links that have missing cached_url.
 *
 * Storyblok's cached_url is "not always updated for caching reasons" (per their docs).
 * This helper resolves the correct URL using the resolved links from the API response,
 * and falls back to an API call by UUID as a last resort.
 *
 * @see https://www.storyblok.com/docs/guide/in-depth/rendering-the-link-field
 */
class StoryblokLinkResolver
{
    private const CACHE_PREFIX = 'storyblok_link_';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Pre-resolved links indexed by UUID, populated by StoryblokService.
     *
     * @var array<string, array>
     */
    private static array $resolvedLinks = [];

    /**
     * Store resolved links for the current request.
     * Called by StoryblokService after processing the API response.
     */
    public static function setResolvedLinks(array $links): void
    {
        self::$resolvedLinks = $links;
    }

    /**
     * Get the currently stored resolved links.
     *
     * @return array<string, array>
     */
    public static function getResolvedLinks(): array
    {
        return self::$resolvedLinks;
    }

    /**
     * Resolve a link array to a URL.
     *
     * Uses RequestCtx (available globally via laravel-platform middleware)
     * to determine brand prefix removal and language prefix insertion.
     *
     * @param  array|null  $link  The link array from Storyblok
     * @param  string  $lang  Language code (used for UUID API fallback)
     * @return string The resolved URL or empty string
     */
    public static function resolve(?array $link, string $lang = 'en'): string
    {
        if (! $link) {
            try {
                $context = app(StoryblokContextResolver::class)->current();
                $link = $context->get('link');
            } catch (\Throwable) {
                $link = '';
            }
        }

        if (! $link) {
            return '';
        }

        // Priority 1: Section_id for same-page navigation
        if ($link['Section_id'] ?? null) {
            return '#'.$link['Section_id'];
        }

        // Priority 2: url field (external links entered manually)
        if ($link['url'] ?? null) {
            return self::normalizeExternal($link['url']);
        }

        // Priority 3: story.url (resolved by Storyblok API)
        if ($link['story']['url'] ?? null) {
            return self::toPublicUrl('/'.$link['story']['url']);
        }

        // Priority 4: story.full_slug
        if ($link['story']['full_slug'] ?? null) {
            return self::toPublicUrl('/'.$link['story']['full_slug']);
        }

        // Priority 5: cached_url (fallback, may be outdated per Storyblok docs)
        if ($link['cached_url'] ?? null) {
            return self::toPublicUrl('/'.trim($link['cached_url']));
        }

        // Priority 6: Use pre-resolved links or fetch by UUID as last resort
        if (! empty($link['id']) && ($link['linktype'] ?? '') === 'story') {
            $uuid = $link['id'];

            if (isset(self::$resolvedLinks[$uuid]['url'])) {
                return self::toPublicUrl('/'.self::$resolvedLinks[$uuid]['url']);
            }

            if (isset(self::$resolvedLinks[$uuid]['slug'])) {
                return self::toPublicUrl('/'.self::$resolvedLinks[$uuid]['slug']);
            }

            return self::resolveByUuid($uuid, $lang);
        }

        return '';
    }

    /**
     * Resolve a link by UUID when cached_url is empty.
     * This makes an additional API call as last resort.
     */
    private static function resolveByUuid(string $uuid, string $lang): string
    {
        $cacheKey = self::CACHE_PREFIX.$uuid.'_'.$lang;
        $ctx = app(RequestCtx::class);

        if ($cached = Cache::get($cacheKey)) {
            return self::toPublicUrl($cached);
        }

        try {
            $storyblok = app(StoryblokGateway::class);
            $requests = app(StoryblokRequestFactory::class);
            $story = $storyblok
                ->getStoryByUuid(new Uuid($uuid), $ctx->storyblokRequest($requests))
                ->story;

            if ($story && ($story['full_slug'] ?? null)) {
                $url = self::toPublicUrl('/'.$story['full_slug']);

                Cache::put($cacheKey, $url, self::CACHE_TTL);

                Log::info('StoryblokLinkResolver: Resolved link by UUID', [
                    'uuid' => $uuid,
                    'url' => $url,
                ]);

                return $url;
            }
        } catch (\Exception $e) {
            Log::warning('StoryblokLinkResolver: Failed to resolve link by UUID', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);
        }

        return '';
    }

    /**
     * Clear the cache for a specific UUID.
     */
    public static function clearCache(string $uuid): void
    {
        foreach (['en', 'es'] as $lang) {
            Cache::forget(self::CACHE_PREFIX.$uuid.'_'.$lang);
        }
    }

    /**
     * Convert a Storyblok internal URL to a public-facing URL.
     *
     * This does three things using RequestCtx (set by laravel-platform middleware):
     * 1. Strips the brand prefix (/brands/mousai/... → /puerto-vallarta/...)
     * 2. Adds /es prefix when the current request is in Spanish
     * 3. Forces HTTPS on any http:// URL
     *
     * @see ResolveRequestCtx
     */
    private static function toPublicUrl(string $url): string
    {
        try {
            $ctx = app(RequestCtx::class);
            $rawUrl = ltrim(trim($url), '/');

            if (str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, 'https://')) {
                return self::normalizeExternal($rawUrl);
            }

            // 1. Strip brand prefix: /brands/[any-brand]/pv/suites → /pv/suites
            // Uses regex to handle any brand (e.g. garza-blanca, mousai, villa-premiere)
            $cleaned = '/'.ltrim(preg_replace('#^brands/[^/]+/?#', '', $rawUrl), '/');

            // 2. Add /es prefix for Spanish (mirrors ResolveRequestCtx URL structure)
            if ($ctx->locale->value === 'es' && ! str_contains($cleaned, '/es/') && ! str_starts_with($cleaned, '/es')) {
                $cleaned = '/es'.$cleaned;
            }

            // 3. Force HTTPS
            if (str_starts_with($cleaned, 'http://')) {
                $cleaned = str_replace('http://', 'https://', $cleaned);
            }

            return $cleaned;
        } catch (\Exception $e) {
            Log::error('StoryblokLinkResolver::toPublicUrl error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return $url;
        }
    }

    /**
     * Normalize an external URL (from the `url` field).
     * Forces HTTPS to prevent mixed-content browser warnings.
     */
    private static function normalizeExternal(string $url): string
    {
        if (str_starts_with($url, 'http://')) {
            return str_replace('http://', 'https://', $url);
        }

        return $url;
    }
}
