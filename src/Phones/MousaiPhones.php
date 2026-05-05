<?php

namespace TAFER\Core\Support\Phones;

use TAFER\Core\Contracts\ResortPhoneDirectory;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\PhoneSource;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Records\PhoneNumber;

/**
 * Phone directory for Hotel Mousai.
 */
final class HotelMousaiPhoneDirectory implements ResortPhoneDirectory
{
    /**
     * Key used to group campaign-specific phone numbers.
     */
    private const CAMPAIGNS_KEY = 'campaigns';

    /**
     * Get the resort handled by this directory.
     *
     * @return Resort Hotel Mousai resort enum case.
     */
    public function resort(): Resort
    {
        return Resort::HotelMousai;
    }

    /**
     * Resolve a Hotel Mousai phone number.
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
     * Resolve a default Hotel Mousai phone number by device.
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
     * Resolve a campaign-specific Hotel Mousai phone number.
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
     * Get the Hotel Mousai phone directory.
     *
     * @return array<string, array<string, array{
     *     desktop?: PhoneNumber,
     *     mobile?: PhoneNumber,
     *     campaigns?: array<string, PhoneNumber>
     * }>>
     */
    private function phones(): array
    {
        return [
            Location::Cancun->value => [
                Locale::Spanish->value => [
                    Device::Desktop->value => new PhoneNumber(
                        number: '+528003231161',
                        buttonText: '+52 800 323 1161',
                    ),
                    Device::Mobile->value => new PhoneNumber(
                        number: '+528006490663',
                        buttonText: '+52 800 649 0663',
                    ),
                ],

                Locale::English->value => [
                    Device::Desktop->value => new PhoneNumber(
                        number: '+18009843015',
                        buttonText: '+1 800 984 3015',
                    ),
                    Device::Mobile->value => new PhoneNumber(
                        number: '+18449330851',
                        buttonText: '+1 844 933 0851',
                    ),
                    self::CAMPAIGNS_KEY => [
                        PhoneSource::Email->value => new PhoneNumber(
                            number: '+17864824948',
                            buttonText: '+1 786 482 4948',
                        ),
                        PhoneSource::Fads->value => new PhoneNumber(
                            number: '+17864713314',
                            buttonText: '+1 786 471 3314',
                        ),
                        PhoneSource::Gads->value => new PhoneNumber(
                            number: '+17864493994',
                            buttonText: '+1 786 449 3994',
                        ),
                    ],
                ],
            ],

            Location::PuertoVallarta->value => [
                Locale::Spanish->value => [
                    Device::Desktop->value => new PhoneNumber(
                        number: '+528003230638',
                        buttonText: '+52 800 323 0638',
                    ),
                    Device::Mobile->value => new PhoneNumber(
                        number: '+528006490491',
                        buttonText: '+52 800 649 0491',
                    ),
                ],

                Locale::English->value => [
                    Device::Desktop->value => new PhoneNumber(
                        number: '+18009314613',
                        buttonText: '+1 800 931 4613',
                    ),
                    Device::Mobile->value => new PhoneNumber(
                        number: '+18448300507',
                        buttonText: '+1 844 830 0507',
                    ),
                    self::CAMPAIGNS_KEY => [
                        PhoneSource::Email->value => new PhoneNumber(
                            number: '+16056100618',
                            buttonText: '+1 605 610 0618',
                        ),
                        PhoneSource::Fads->value => new PhoneNumber(
                            number: '+16028132029',
                            buttonText: '+1 602 813 2029',
                        ),
                        PhoneSource::Gads->value => new PhoneNumber(
                            number: '+16028132054',
                            buttonText: '+1 602 813 2054',
                        ),
                    ],
                ],
            ],
        ];
    }
}