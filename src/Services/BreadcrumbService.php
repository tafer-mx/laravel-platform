<?php

namespace TAFER\Core\Services;

use Storyblok\Api\Domain\Value\Uuid;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Contracts\StoryblokGateway;
use TAFER\Core\Storyblok\StoryblokRequestFactory;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class BreadcrumbService
{
    public function __construct(
        protected StoryblokGateway $storyblok,
        protected StoryblokRequestFactory $requests,
        protected RequestCtx $ctx,
    ) {}

    /**
     * Generate breadcrumbs from URL slug with optional CMS override
     */
    public function generateBreadcrumbs(string $fullSlug, mixed $cmsOverride = null): array
    {
        // If fullSlug is empty, try to get from current request
        if (empty($fullSlug)) {
            $fullSlug = ltrim(request()->path(), '/');
        }

        // Always prioritize URL-based generation for consistency
        $urlBasedBreadcrumbs = $this->generateFromSlug($fullSlug);

        // If we have URL-based breadcrumbs, use them
        if (! empty($urlBasedBreadcrumbs)) {
            return $urlBasedBreadcrumbs;
        }

        // Only fallback to CMS override if URL generation fails
        if ($this->hasValidCmsOverride($cmsOverride)) {
            $cmsResult = $this->processCmsOverride($cmsOverride);
            if (! empty($cmsResult)) {
                return $cmsResult;
            }
        }

        return [];
    }

    /**
     * Check if CMS override contains valid breadcrumb data
     */
    private function hasValidCmsOverride(mixed $cmsOverride): bool
    {
        if (empty($cmsOverride) || ! is_array($cmsOverride)) {
            return false;
        }

        // Check if it contains UUID references
        foreach ($cmsOverride as $item) {
            if (is_string($item) && $this->isUuid($item)) {
                return true;
            }
            if (is_array($item) && isset($item['brearcrum'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process CMS override breadcrumb data
     */
    private function processCmsOverride(array $cmsOverride): array
    {
        $breadcrumbs = [];

        try {
            // Extract UUID from nested structure (similar to current logic but simplified)
            $finalUuid = $this->extractFinalUuid($cmsOverride);

            if ($finalUuid) {
                $story = $this->storyblok
                    ->getStoryByUuid(
                        new Uuid($finalUuid),
                        $this->ctx->storyblokRequest($this->requests),
                    )
                    ->story;

                if ($story && isset($story['full_slug'])) {
                    return $this->generateFromSlug($story['full_slug']);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't break the page
            \Log::warning('Failed to process CMS breadcrumb override', [
                'error' => $e->getMessage(),
                'override' => $cmsOverride,
            ]);
        }

        return $breadcrumbs;
    }

    /**
     * Generate breadcrumbs from URL slug using intelligent mapping
     */
    private function generateFromSlug(string $fullSlug): array
    {
        if (empty($fullSlug)) {
            return [];
        }

        $breadcrumbs = [];
        $segments = array_filter(explode('/', trim($fullSlug, '/')));

        // Detect language from URL
        $locale = $this->detectLocale($segments);

        // Filter out unwanted segments
        $filteredSegments = $this->filterSegments($segments);

        if (empty($filteredSegments)) {
            return [];
        }

        // Get site configuration based on the first relevant segment
        $siteConfig = $this->getSiteConfiguration($filteredSegments);

        // Build breadcrumb path
        foreach ($filteredSegments as $index => $segment) {
            // Handle home page specially
            if ($index === 0 && isset($siteConfig['home'])) {
                $breadcrumbs[] = [
                    'text' => $this->translate('Home', $locale),
                    'link' => $this->addLocaleToUrl($siteConfig['home']['url'], $locale),
                    'icon' => 'home',
                ];
                // DO NOT add location breadcrumb - it's redundant with Home
            } else {
                // Get display name for segment
                $displayName = $this->getSegmentDisplayName($segment, $siteConfig, $locale);

                // Build correct URL path up to current segment
                $currentPath = $this->buildUrlPath($filteredSegments, $index, $siteConfig, $locale);

                $breadcrumbs[] = [
                    'text' => $displayName,
                    'link' => $currentPath,
                ];
            }
        }

        return $breadcrumbs;
    }

    /**
     * Filter out unwanted URL segments
     */
    private function filterSegments(array $segments): array
    {
        $filteredSegments = [];

        foreach ($segments as $segment) {
            $segmentLower = strtolower($segment);

            // Skip language codes (es, en, fr, etc.)
            if (in_array($segmentLower, ['es', 'en', 'fr', 'de', 'pt'])) {
                continue;
            }

            // Skip unwanted segments
            if (in_array($segmentLower, ['v2', 'garza-blanca', 'brands'])) {
                continue;
            }

            $filteredSegments[] = $segment;
        }

        return $filteredSegments;
    }

    /**
     * Get site configuration based on location segment
     */
    private function getSiteConfiguration(array $segments): array
    {
        $locationSegment = strtolower($segments[0] ?? '');

        $configurations = [
            'home' => [
                'name' => 'Garza Blanca',
                'home' => [
                    'url' => '/brands/garza-blanca/home',
                ],
                'base_path' => '/brands/garza-blanca',
                'segments' => [
                    'suites' => 'Suites',
                    'residences' => 'Residences',
                    'dining' => 'Dining',
                    'wellness' => 'Wellness',
                    'experiences' => 'Experiences',
                    'weddings' => 'Weddings',
                    'meetings' => 'Meetings & Events',
                    'blog' => 'Blog',
                    'press-releases' => 'Press Releases',
                ],
                'special_pages' => [],
            ],
            'cancun' => [
                'name' => 'Cancún',
                'home' => [
                    'url' => '/cancun',
                    'legacy_url' => '/brands/garza-blanca/cancun/home',
                ],
                'base_path' => '/cancun',
                'segments' => [
                    'suites' => 'Suites',
                    'residences' => 'Residences',
                    'penthouses' => 'Penthouses',
                    'dining' => 'Dining',
                    'wellness' => 'Wellness',
                    'experiences' => 'Experiences',
                    'weddings' => 'Weddings',
                    'meetings' => 'Meetings & Events',
                    'blog' => 'Blog',
                    'press-releases' => 'Press Releases',
                ],
                'special_pages' => [
                    'suites' => '/cancun/suites/',
                    'residences' => '/cancun/residences/',
                    'penthouses' => '/cancun/penthouses/',
                ],
            ],
            'los-cabos' => [
                'name' => 'Los Cabos',
                'home' => [
                    'url' => '/los-cabos/',
                ],
                'base_path' => '/los-cabos',
                'segments' => [
                    'suites' => 'Suites',
                    'residences' => 'Residences',
                    'penthouses' => 'Penthouses',
                    'dining' => 'Dining',
                    'wellness' => 'Wellness',
                    'experiences' => 'Experiences',
                    'weddings' => 'Weddings',
                    'meetings' => 'Meetings & Events',
                    'blog' => 'Blog',
                    'press-releases' => 'Press Releases',
                ],
                'special_pages' => [
                    'suites' => '/los-cabos/suites/suites',
                    'residences' => '/los-cabos/residences/residences',
                    'penthouses' => '/los-cabos/Penthouses/Penthouses',
                ],
            ],
            'puerto-vallarta' => [
                'name' => 'Puerto Vallarta',
                'home' => [
                    'url' => '/puerto-vallarta',
                ],
                'base_path' => '/puerto-vallarta',
                'segments' => [
                    'suites' => 'Suites',
                    'residences' => 'Residences',
                    'penthouses' => 'Penthouses',
                    'dining' => 'Dining',
                    'wellness' => 'Wellness',
                    'experiences' => 'Experiences',
                    'weddings' => 'Weddings',
                    'meetings' => 'Meetings & Events',
                    'blog' => 'Blog',
                    'press-releases' => 'Press Releases',
                ],
                'special_pages' => [
                    'suites' => '/puerto-vallarta/suites/suites',
                    'residences' => '/puerto-vallarta/residences/residences',
                    'penthouses' => '/puerto-vallarta/Penthouses/Penthouses',
                ],
            ],
        ];

        return $configurations[$locationSegment] ?? [
            'name' => ucwords(str_replace('-', ' ', $locationSegment)),
            'home' => ['url' => '/'],
            'base_path' => '',
            'segments' => [],
            'special_pages' => [],
        ];
    }

    /**
     * Check if a segment is a section page (dining, suites, residences, etc.)
     */
    private function isSectionPage(string $segment, array $siteConfig): bool
    {
        $segmentLower = strtolower($segment);

        // Check if segment exists in the predefined sections list
        if (isset($siteConfig['segments'][$segmentLower])) {
            return true;
        }

        return false;
    }

    /**
     * Get display name for a URL segment
     */
    private function getSegmentDisplayName(string $segment, array $siteConfig, string $locale = 'en'): string
    {
        $segmentLower = strtolower($segment);

        // Check if we have a predefined name for this segment
        if (isset($siteConfig['segments'][$segmentLower])) {
            $englishName = $siteConfig['segments'][$segmentLower];

            return $this->translate($englishName, $locale);
        }

        // Convert kebab-case to title case
        $titleCase = ucwords(str_replace('-', ' ', $segment));

        return $this->translate($titleCase, $locale);
    }

    /**
     * Build URL path correctly for breadcrumb segment
     */
    private function buildUrlPath(array $filteredSegments, int $currentIndex, array $siteConfig, string $locale = 'en'): string
    {
        $basePath = $siteConfig['base_path'] ?? '';
        $currentSegment = $filteredSegments[$currentIndex] ?? '';
        $specialPages = $siteConfig['special_pages'] ?? [];

        // Check if this is a section page at index 1 (after location)
        if ($currentIndex === 1) {
            $segmentLower = strtolower($currentSegment);

            // Check if this segment is a known section (suites, dining, etc.)
            if ($this->isSectionPage($currentSegment, $siteConfig)) {
                // Use clean URL without duplication (orchestrator will handle redirect)
                // Example: /cancun/residences instead of /cancun/residences/residences
                return $this->addLocaleToUrl($basePath.'/'.$currentSegment, $locale);
            }
        }

        // Get segments up to current index
        $pathSegments = array_slice($filteredSegments, 0, $currentIndex + 1);

        // Avoid duplicate location segments in URL path
        $locationSegment = strtolower($filteredSegments[0] ?? '');
        $cleanedSegments = [];

        // Extract location from base path to avoid duplication
        $basePathSegments = array_filter(explode('/', trim($basePath, '/')));
        $lastBaseSegment = end($basePathSegments);

        foreach ($pathSegments as $segment) {
            // Skip if this segment is the same as the last segment in base path
            if (strtolower($segment) !== strtolower($lastBaseSegment)) {
                $cleanedSegments[] = $segment;
            }
        }

        // Build the path
        if (empty($cleanedSegments)) {
            return $this->addLocaleToUrl($basePath, $locale);
        }

        return $this->addLocaleToUrl($basePath.'/'.implode('/', $cleanedSegments), $locale);
    }

    /**
     * Extract final UUID from nested breadcrumb structure
     */
    private function extractFinalUuid(array $data, int $maxDepth = 5): ?string
    {
        if ($maxDepth <= 0) {
            return null;
        }

        foreach ($data as $item) {
            if (is_string($item) && $this->isUuid($item)) {
                return $item;
            } elseif (is_array($item) && isset($item['brearcrum']) && ! empty($item['brearcrum'])) {
                $result = $this->extractFinalUuid($item['brearcrum'], $maxDepth - 1);
                if ($result) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Check if a string looks like a UUID
     */
    private function isUuid(string $string): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $string) === 1;
    }

    /**
     * Detect locale from URL segments
     */
    private function detectLocale(array $segments): string
    {
        // Check if first segment is a language code
        if (! empty($segments)) {
            $firstSegment = strtolower($segments[0]);
            if (in_array($firstSegment, ['es', 'en', 'fr', 'de', 'pt'])) {
                return $firstSegment;
            }
        }

        return 'en'; // default
    }

    /**
     * Add locale prefix to URL if not English
     */
    private function addLocaleToUrl(string $url, string $locale = 'en'): string
    {
        // Don't add prefix for English (default language)
        if ($locale === 'en' || empty($locale)) {
            return $url;
        }

        // If URL already has locale prefix, return as is
        if (preg_match('#^/'.$locale.'/#', $url)) {
            return $url;
        }

        // Add locale prefix
        return '/'.$locale.$url;
    }

    /**
     * Translate breadcrumb text based on locale
     */
    private function translate(string $text, string $locale = 'en'): string
    {
        if ($locale === 'en') {
            return $text;
        }

        // Spanish translations
        $translations = [
            'es' => [
                'Home' => 'Inicio',
                'Suites' => 'Suites',
                'Residences' => 'Residencias',
                'Penthouses' => 'Penthouses',
                'Dining' => 'Gastronomía',
                'Wellness' => 'Bienestar',
                'Experiences' => 'Experiencias',
                'Weddings' => 'Bodas',
                'Meetings & Events' => 'Reuniones y Eventos',
                'Blog' => 'Blog',
                'Press Releases' => 'Comunicados de Prensa',
                'Puerto Vallarta' => 'Puerto Vallarta',
                'Cancún' => 'Cancún',
                'Los Cabos' => 'Los Cabos',
                'Garza Blanca' => 'Garza Blanca',
            ],
        ];

        return $translations[$locale][$text] ?? $text;
    }
}
