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
final class HotelMousaiPhoneDirectory extends PhoneDirectory implements ResortPhoneDirectory
{

    public function resort(): Resort
    {
        return Resort::HotelMousai;
    }

    protected function phones(): array
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