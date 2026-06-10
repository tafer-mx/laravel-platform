<?php

namespace TAFER\Core\Records;

use TAFER\Core\Enums\Location;

/**
 * Immutable record that represents a specific resort region.
 */
final readonly class ResortRegion
{
    /**
     * @param  Location  $location  Region/location where the resort exists.
     * @param  string  $code  Internal code for this resort in this specific region.
     */
    public function __construct(
        public Location $location,
        public string $code,
    ) {}
}
