<?php

namespace TAFER\Core\Contracts;

use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\PhoneSource;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Records\PhoneNumber;

/**
 * Contract for resort-specific phone directories.
 */
interface ResortPhoneDirectory
{
    /**
     * Get the resort handled by this directory.
     *
     * @return Resort Resort enum case handled by this directory.
     */
    public function resort(): Resort;

    /**
     * Resolve a phone number for the resort.
     *
     * When `$source` is provided, the implementation should resolve a
     * campaign-specific phone number. Otherwise, it should resolve the default
     * phone number for the given device.
     *
     * @param Location $location Location used to resolve the phone number.
     * @param Locale $locale Locale used to resolve the phone number.
     * @param Device $device Device used for default phone numbers.
     * @param PhoneSource|null $source Optional campaign/source.
     *
     * @return PhoneNumber|null Matching phone number, or null when not configured.
     */
    public function get(
        Location $location,
        Locale $locale,
        Device $device = Device::Desktop,
        ?PhoneSource $source = null,
    ): ?PhoneNumber;
}