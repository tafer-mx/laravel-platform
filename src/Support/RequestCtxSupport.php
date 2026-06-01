<?php 

namespace TAFER\Core\Support;

use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;

use Illuminate\Support\Str;

class RequestCtxSupport
{
    /**
     * Determines the locale based on the first URL segment.
     *
     * Behavior rules:
     *  - If the URL starts with `/en`, the locale is "en" **and is treated as explicit**.
     *    This is used to trigger a redirect to the implicit English version (URL without `/en`).
     *
     *  - If the URL starts with `/es`, the locale is "es" **but NOT explicit**.
     *    Spanish should preserve its prefix and should NOT trigger redirects.
     *
     *  - If the URL does not contain a locale prefix, the locale defaults to "en"
     *    and is considered implicit.
     *
     * Returned structure:
     *   [
     *     'locale'   => string  ("en" or "es"),
     *     'explicit' => bool    Whether the locale was explicitly present in the URL
     *                            and should trigger locale-specific behavior
     *                            (e.g., redirect for explicit /en).
     *   ]
     *
     * @param  string[]  $segments  URL segments from the request
     * @return array{locale: Locale, explicit: bool}
     */
    public static function getLocaleBySegments(array $segments): array
    {
        $segments = collect($segments)
            ->map(fn ($seg) => Str::lower($seg))
            ->toArray();

        $locale = collect(Locale::cases())
            ->first(fn (Locale $loc) => in_array($loc->value, $segments));
    

        if($locale === Locale::English) {
            return [
                'locale' => $locale,
                'explicit' => true
            ];
        }

        if($locale === Locale::Spanish) {
            return [
                'locale' => $locale,
                'explicit' => true
            ];
        }

        //English implícito
        return [
            'locale' => Locale::English,
            'explicit' => false
        ];
    }

    /**
     * Resolve the current location from URL segments.
     *
     * The method scans the request segments for a known Location slug.
     * If no matching location is found, it defaults to the corporate location.
     *
     * @param  string[]  $segments  URL segments from the request
     * @return Location
     */
    public static function getLocationBySegments(array $segments): Location
    {
        $segments = collect($segments)
            ->map(fn ($seg) => Str::lower($seg))
            ->toArray();

        $location = collect(Location::cases())
            ->first(fn (Location $loc) => in_array($loc->value, $segments));
        
        return $location ? $location : Location::Corp;
    }

    /**
     * Return the URI slug after removing a locale prefix.
     *
     * If the first segment is a recognized locale value, it is removed.
     * The remaining segments are joined back into a slug. For the root
     * path this method returns '/'.
     *
     * @param  string[]  $segments  URL segments from the request
     * @return string
     */
    public static function getSlugWithoutLocaleBySegments(array $segments): string
    {
        $locales = array_map(
            fn (Locale $locale) => $locale->value,
            Locale::cases()
        );

        if (isset($segments[0]) && in_array($segments[0], $locales, true)) {
            array_shift($segments);
        }

        $slug = implode('/', $segments);
        return $slug === '' ? '/' : $slug;
    }
}