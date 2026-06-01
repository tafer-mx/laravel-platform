<?php

use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\PhoneSource;
use TAFER\Core\Phones\MousaiPhoneDirectory;
use TAFER\Core\Records\PhoneNumber;

describe('MSCN Phone Directory', function () {
    describe('English', function(){
        it('gets the Cancun English desktop phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                device: Device::Desktop,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+18009843015')
                ->and($phone->buttonText)->toBe('+1 800 984 3015');
        });

        it('gets the Cancun English mobile phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                device: Device::Mobile,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+18449330851')
                ->and($phone->buttonText)->toBe('+1 844 933 0851');
        });

        it('gets the Cancun English email campaign phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                source: PhoneSource::Email,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+17864824948')
                ->and($phone->buttonText)->toBe('+1 786 482 4948');
        });

        it('gets the Cancun English FADS campaign phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                source: PhoneSource::Fads,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+17864713314')
                ->and($phone->buttonText)->toBe('+1 786 471 3314');
        });

        it('gets the Cancun English GADS campaign phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                source: PhoneSource::Gads,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+17864493994')
                ->and($phone->buttonText)->toBe('+1 786 449 3994');
        });
   });

   describe('Spanish', function () {
        it('gets the Cancun Spanish desktop phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::Spanish,
                device: Device::Desktop,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+528003231161')
                ->and($phone->buttonText)->toBe('+52 800 323 1161');
        });

        it('gets the Cancun Spanish mobile phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::Spanish,
                device: Device::Mobile,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+528006490663')
                ->and($phone->buttonText)->toBe('+52 800 649 0663');
        });
   });
});

describe('MSPV Phone Directory', function () {
    describe('English', function () {
        it('gets the Puerto Vallarta English desktop phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::English,
                device: Device::Desktop,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+18009314613')
                ->and($phone->buttonText)->toBe('+1 800 931 4613');
        });

        it('gets the Puerto Vallarta English Mobile phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::English,
                device: Device::Mobile,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+18448300507')
                ->and($phone->buttonText)->toBe('+1 844 830 0507');
        });

        it('gets the Puerto Vallarta English Email phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::English,
                source: PhoneSource::Email,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+16056100618')
                ->and($phone->buttonText)->toBe('+1 605 610 0618');
        });
        
        it('gets the Puerto Vallarta English FADS phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::English,
                source: PhoneSource::Fads,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+16028132029')
                ->and($phone->buttonText)->toBe('+1 602 813 2029');
        });

        it('gets the Puerto Vallarta English Gads phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::English,
                source: PhoneSource::Gads,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+16028132054')
                ->and($phone->buttonText)->toBe('+1 602 813 2054');
        });
    });

    describe('Spanish', function() {
        it('gets the Puerto Vallarta Spanish desktop phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::Spanish,
                device: Device::Desktop,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+528003230638')
                ->and($phone->buttonText)->toBe('+52 800 323 0638');
        });

        it('gets the Puerto Vallarta Spanish Mobile phone number for Mousai', function () {
            $directory = new MousaiPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::Spanish,
                device: Device::Mobile,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+528006490491')
                ->and($phone->buttonText)->toBe('+52 800 649 0491');
        });
    });
});