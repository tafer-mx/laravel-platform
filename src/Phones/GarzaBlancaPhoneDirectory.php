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
 * Phone directory for Garza Blanca.
 */
final readonly class GarzaBlancaPhoneDirectory extends PhoneDirectory implements ResortPhoneDirectory
{

    public function resort(): Resort
    {
        return Resort::GarzaBlanca;
    }

    protected function phones(): array
    {
        return [
            Location::Cancun->value => [
                Locale::Spanish->value => [
                    Device::Desktop->value => new PhoneNumber(
                        '+528003230744',
                        '+52 800 323 0744'
                    ),
                    Device::Mobile->value => new PhoneNumber(
                        '+528006490462',
                        '+52 800 649 0462'
                    )
                ],
                Locale::English->value => [
                    Device::Desktop->value => new PhoneNumber(
                        '+18009270503',
                        '+1 800 927 0503'
                    ),
                    Device::Mobile->value => new PhoneNumber(
                        '+18449270506',
                        '+1 844 933 0506'
                    ),
                    self::CAMPAIGNS_KEY => [
                        PhoneSource::Email->value => new PhoneNumber(
                            '+16028132942',
                            '+1 602 813 2942'
                        ),
                        PhoneSource::Fads->value => new PhoneNumber(
                            '+14158811174',
                            '+1 415 881 1174'
                        ),
                        PhoneSource::Gads->value => new PhoneNumber(
                            '+14452134644',
                            '+1 445 213 4644'
                        )
                    ]
                ]
            ],
            Location::Cabo->value => [
                Locale::Spanish->value => [
                    Device::Desktop->value => new PhoneNumber(
                        '+528003230715',
                        '+52 800 323 0715'
                    ),
                    Device::Mobile->value => new PhoneNumber(
                        '+528006490552',
                        '+52 800 649 0552'
                    )
                ],
                Locale::English->value => [
                    Device::Desktop->value => new PhoneNumber(
                        '+18009316023',
                        '+1 800 931 6023'
                    ),
                    Device::Mobile->value => new PhoneNumber(
                        '+18447829544',
                        '+1 844 782 9544'
                    ),
                    self::CAMPAIGNS_KEY => [
                        PhoneSource::Email->value => new PhoneNumber(
                            '+16056104421',
                            '+1 605 610 4421'
                        ),
                        PhoneSource::Fads->value => new PhoneNumber(
                            '+16026716747',
                            '+1 602 671 6747'
                        ),
                        PhoneSource::Gads->value => new PhoneNumber(
                            '+14806482672',
                            '+1 480 648 2672'
                        )
                    ]
                ]
            ],
            Location::PuertoVallarta->value => [
                Locale::Spanish->value => [
                    Device::Desktop->value => new PhoneNumber(
                        '+528003230729',
                        '+52 800 323 0729'
                    ),
                    Device::Mobile->value => new PhoneNumber(
                        '+528006490485',
                        '+52 800 649 0485'
                    )
                ],
                Locale::English->value => [
                    Device::Desktop->value => new PhoneNumber(
                        '+18009314614',
                        '+1 800 931 4614'
                    ),
                    Device::Mobile->value => new PhoneNumber(
                        '+18449330896',
                        '+1 844 933 0896'
                    ),
                    self::CAMPAIGNS_KEY => [
                        PhoneSource::Email->value => new PhoneNumber(
                            '+16054035754',
                            '+1 605 403 5754'
                        ),
                        PhoneSource::Fads->value => new PhoneNumber(
                            '+16026716621',
                            '+1 602 671 6621'
                        ),
                        PhoneSource::Gads->value => new PhoneNumber(
                            '+14158397285',
                            '+1 415 839 7285'
                        ),
                    ]
                ]
            ]
        ];
    }
}
