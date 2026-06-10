<?php

namespace TAFER\Core\Phones;

use TAFER\Core\Contracts\ResortPhoneDirectory;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\PhoneSource;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Records\PhoneNumber;

/**
 * Phone directory for Villa Palmar.
 */
final readonly class VillaPalmarPhoneDirectory extends PhoneDirectory implements ResortPhoneDirectory
{
    public function resort(): Resort
    {
        return Resort::VillaPalmarCancun;
    }

    protected function phones(): array
    {
        return [
            Location::Cancun->value => [
                Locale::Spanish->value => [
                    Device::Desktop->value => new PhoneNumber(
                        '+528003230763',
                        '+52 800 323 0763'
                    ),
                    Device::Mobile->value => new PhoneNumber(
                        '+528006490442',
                        '+52 800 649 0442'
                    ),
                ],
                Locale::English->value => [
                    Device::Desktop->value => new PhoneNumber(
                        '+18009314608',
                        '+1 800 931 4608'
                    ),
                    Device::Mobile->value => new PhoneNumber(
                        '+18449257740',
                        '+1 844 925 7740'
                    ),
                    self::CAMPAIGNS_KEY => [
                        PhoneSource::Email->value => new PhoneNumber(
                            '+16054035776',
                            '+1 605 403 5776'
                        ),
                        PhoneSource::Fads->value => new PhoneNumber(
                            '+16026716819',
                            '+1 602 671 6819'
                        ),
                        PhoneSource::Gads->value => new PhoneNumber(
                            '+16028132073',
                            '+1 602 813 2073'
                        ),
                    ],
                ],
            ],
        ];
    }
}
