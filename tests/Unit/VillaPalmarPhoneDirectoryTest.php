<?php

use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\PhoneSource;
use TAFER\Core\Phones\VillaPalmarPhoneDirectory;
use TAFER\Core\Records\PhoneNumber;

describe('VPCN Phone Directory', function () {
    describe('English', function () {
        it('gets the English desktop phone number for Villa Palmar Cancun', function () {
            $directory = new VillaPalmarPhoneDirectory;

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                device: Device::Desktop,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+18009314608')
                ->and($phone->buttonText)->toBe('+1 800 931 4608');
        });

        it('gets the English mobile phone number for Villa Palmar Cancun', function () {
            $directory = new VillaPalmarPhoneDirectory;

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                device: Device::Mobile,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+18449257740')
                ->and($phone->buttonText)->toBe('+1 844 925 7740');
        });

        it('gets the English email campaign phone number for Villa Palmar Cancun', function () {
            $directory = new VillaPalmarPhoneDirectory;

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                source: PhoneSource::Email,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+16054035776')
                ->and($phone->buttonText)->toBe('+1 605 403 5776');
        });

        it('gets the English FADS campaign phone number for Villa Palmar Cancun', function () {
            $directory = new VillaPalmarPhoneDirectory;

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                source: PhoneSource::Fads,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+16026716819')
                ->and($phone->buttonText)->toBe('+1 602 671 6819');
        });

        it('gets the English GADS campaign phone number for Villa Palmar Cancun', function () {
            $directory = new VillaPalmarPhoneDirectory;

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                source: PhoneSource::Gads,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+16028132073')
                ->and($phone->buttonText)->toBe('+1 602 813 2073');
        });
    });

    describe('Spanish', function () {
        it('gets the Spanish desktop phone number for Villa Palmar Cancun', function () {
            $directory = new VillaPalmarPhoneDirectory;

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::Spanish,
                device: Device::Desktop,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+528003230763')
                ->and($phone->buttonText)->toBe('+52 800 323 0763');
        });

        it('gets the Spanish mobile phone number for Villa Palmar Cancun', function () {
            $directory = new VillaPalmarPhoneDirectory;

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::Spanish,
                device: Device::Mobile,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+528006490442')
                ->and($phone->buttonText)->toBe('+52 800 649 0442');
        });
    });
});
