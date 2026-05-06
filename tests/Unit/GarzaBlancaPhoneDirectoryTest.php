<?php

use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\PhoneSource;
use TAFER\Core\Phones\GarzaBlancaPhoneDirectory;
use TAFER\Core\Records\PhoneNumber;

describe('GBCN Phone Directory', function () {
    describe('English', function(){
        it('gets the Cancun English desktop phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                device: Device::Desktop,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+18009270503')
                ->and($phone->buttonText)->toBe('+1 800 927 0503');
        });

        it('gets the Cancun English mobile phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                device: Device::Mobile,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+18449270506')
                ->and($phone->buttonText)->toBe('+1 844 933 0506');
        });

        it('gets the Cancun English email campaign phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                source: PhoneSource::Email,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+16028132942')
                ->and($phone->buttonText)->toBe('+1 602 813 2942');
        });

        it('gets the Cancun English FADS campaign phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                source: PhoneSource::Fads,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+14158811174')
                ->and($phone->buttonText)->toBe('+1 415 881 1174');
        });

        it('gets the Cancun English GADS campaign phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::English,
                source: PhoneSource::Gads,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+14452134644')
                ->and($phone->buttonText)->toBe('+1 445 213 4644');
        });
   });

   describe('Spanish', function () {
        it('gets the Cancun Spanish desktop phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::Spanish,
                device: Device::Desktop,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+528003230744')
                ->and($phone->buttonText)->toBe('+52 800 323 0744');
        });

        it('gets the Cancun Spanish mobile phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cancun,
                locale: Locale::Spanish,
                device: Device::Mobile,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+528006490462')
                ->and($phone->buttonText)->toBe('+52 800 649 0462');
        });
   });
});

describe('GBPV Phone Directory', function () {
    describe('English', function () {
        it('gets the Puerto Vallarta English desktop phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::English,
                device: Device::Desktop,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+18009314614')
                ->and($phone->buttonText)->toBe('+1 800 931 4614');
        });

        it('gets the Puerto Vallarta English Mobile phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::English,
                device: Device::Mobile,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+18449330896')
                ->and($phone->buttonText)->toBe('+1 844 933 0896');
        });

        it('gets the Puerto Vallarta English Email phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::English,
                source: PhoneSource::Email,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+16054035754')
                ->and($phone->buttonText)->toBe('+1 605 403 5754');
        });
        
        it('gets the Puerto Vallarta English FADS phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::English,
                source: PhoneSource::Fads,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+16026716621')
                ->and($phone->buttonText)->toBe('+1 602 671 6621');
        });

        it('gets the Puerto Vallarta English Gads phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::English,
                source: PhoneSource::Gads,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+14158397285')
                ->and($phone->buttonText)->toBe('+1 415 839 7285');
        });
    });

    describe('Spanish', function() {
        it('gets the Puerto Vallarta Spanish desktop phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::Spanish,
                device: Device::Desktop,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+528003230729')
                ->and($phone->buttonText)->toBe('+52 800 323 0729');
        });

        it('gets the Puerto Vallarta Spanish Mobile phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::PuertoVallarta,
                locale: Locale::Spanish,
                device: Device::Mobile,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+528006490485')
                ->and($phone->buttonText)->toBe('+52 800 649 0485');
        });
    });
});

describe('GBLC Phone Directory', function () {
    describe('English', function () {
        it('gets the Los Cabos English desktop phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cabo,
                locale: Locale::English,
                device: Device::Desktop,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+18009316023')
                ->and($phone->buttonText)->toBe('+1 800 931 6023');
        });

        it('gets the Los Cabos English Mobile phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cabo,
                locale: Locale::English,
                device: Device::Mobile,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+18447829544')
                ->and($phone->buttonText)->toBe('+1 844 782 9544');
        });

        it('gets the Los Cabos English Email campaign phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cabo,
                locale: Locale::English,
                source: PhoneSource::Email,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+16056104421')
                ->and($phone->buttonText)->toBe('+1 605 610 4421');
        });

        it('gets the Los Cabos English Fads campaign phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cabo,
                locale: Locale::English,
                source: PhoneSource::Fads,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+16026716747')
                ->and($phone->buttonText)->toBe('+1 602 671 6747');
        });

         it('gets the Los Cabos English Gads campaign phone number for Garza Blanca', function () {
            $directory = new GarzaBlancaPhoneDirectory();

            $phone = $directory->get(
                location: Location::Cabo,
                locale: Locale::English,
                source: PhoneSource::Gads,
            );

            expect($phone)
                ->toBeInstanceOf(PhoneNumber::class)
                ->and($phone->number)->toBe('+14806482672')
                ->and($phone->buttonText)->toBe('+1 480 648 2672');
        });
    });
});