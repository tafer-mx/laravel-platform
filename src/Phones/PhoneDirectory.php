<?php 

namespace TAFER\Core\Phones;

use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\PhoneSource;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Records\PhoneNumber;

abstract class PhoneDirectory
{
    /**
     * Key used to group campaign-specific phone numbers.
     */
    protected const CAMPAIGNS_KEY = 'campaigns';

    /**
     * Get the resort handled by this directory.
     *
     * @return Resort Hotel resort enum case.
     */
    abstract public function resort(): Resort;

    /**
     * Resolve a phone number.
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
    ): ?PhoneNumber {
        if ($source !== null) {
            return $this->campaign(
                location: $location,
                locale: $locale,
                source: $source,
            );
        }

        return $this->default(
            location: $location,
            locale: $locale,
            device: $device,
        );
    }

    /**
     * Resolve a default phone number by device.
     *
     * @param Location $location Location used to resolve the phone number.
     * @param Locale $locale Locale used to resolve the phone number.
     * @param Device $device Device used to resolve the phone number.
     *
     * @return PhoneNumber|null Matching phone number, or null when not configured.
     */
    public function default(
        Location $location,
        Locale $locale,
        Device $device = Device::Desktop,
    ): ?PhoneNumber {
        $phones = $this->phones();

        return $phones[$location->value]
            [$locale->value]
            [$device->value]
            ?? null;
    }

    /**
     * Resolve a campaign-specific phone number.
     *
     * @param Location $location Location used to resolve the phone number.
     * @param Locale $locale Locale used to resolve the phone number.
     * @param PhoneSource $source Campaign/source used to resolve the phone number.
     *
     * @return PhoneNumber|null Matching phone number, or null when not configured.
     */
    public function campaign(
        Location $location,
        Locale $locale,
        PhoneSource $source,
    ): ?PhoneNumber {
        $phones = $this->phones();

        return $phones[$location->value]
            [$locale->value]
            [self::CAMPAIGNS_KEY]
            [$source->value]
            ?? null;
    }

     /**
     * Get the Resort phone directory.
     *
     * @return array<string, array<string, array{
     *     desktop?: PhoneNumber,
     *     mobile?: PhoneNumber,
     *     campaigns?: array<string, PhoneNumber>
     * }>>
     */
    abstract protected function phones(): array;
}