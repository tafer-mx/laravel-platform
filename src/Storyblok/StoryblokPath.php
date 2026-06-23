<?php

namespace TAFER\Core\Storyblok;

use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;

final class StoryblokPath
{
    public static function forResort(
        Resort $resort,
        Location $location,
        string $slug = '',
        string $root = 'brands',
    ): string {
        return self::join($root, $resort->value, $location->value, $slug);
    }

    public static function join(string ...$segments): string
    {
        return implode('/', array_values(array_filter(
            array_map(static fn (string $segment): string => trim($segment, '/'), $segments),
            static fn (string $segment): bool => $segment !== '',
        )));
    }
}
