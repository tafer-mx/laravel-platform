<?php 

use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\PhoneSource;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Phones\PhoneDirectory;
use TAFER\Core\Records\PhoneNumber;

function fakePhoneDirectory()
{
    return new class extends PhoneDirectory {
        public PhoneNumber $desktopPhone;
        public PhoneNumber $mobilePhone;
        public PhoneNumber $campaignPhone;

        public function __construct()
        {
            $this->desktopPhone = new PhoneNumber(
                number: '+52 322 000 0001',
                buttonText: 'Desktop phone',
            );

            $this->mobilePhone = new PhoneNumber(
                number: '+52 322 000 0002',
                buttonText: 'Mobile phone',
            );

            $this->campaignPhone = new PhoneNumber(
                number: '+52 322 000 0003',
                buttonText: 'Campaign phone',
            );
        }

        public function resort(): Resort
        {
            return Resort::HotelMousai;
        }

        protected function phones(): array
        {
            return [
                Location::PuertoVallarta->value => [
                    Locale::English->value => [
                        Device::Desktop->value => $this->desktopPhone,
                        Device::Mobile->value => $this->mobilePhone,
                        self::CAMPAIGNS_KEY => [
                            PhoneSource::Email->value => $this->campaignPhone,
                        ],
                    ],
                ],
            ];
        }
    };
}


it('Should resolves a default desktop phone number', function () {
    $directory = fakePhoneDirectory();

    $phoneEnglish = $directory->default(
        location: Location::PuertoVallarta,
        locale: Locale::English,
        device: Device::Desktop,
    );

    $phoneSpanish = $directory->default(
        location: Location::PuertoVallarta,
        locale: Locale::Spanish,
        device: Device::Desktop,
    );

    expect($phoneEnglish)->toBe($directory->desktopPhone)->and($phoneSpanish)->toBe($directory->default(
        location: Location::PuertoVallarta,
        locale: Locale::Spanish,
        device: Device::Desktop,
    ));
});

it('Should resolves a default mobile phone number', function () {
    $directory = fakePhoneDirectory();

    $phoneEnglish = $directory->default(
        location: Location::PuertoVallarta,
        locale: Locale::English,
        device: Device::Mobile,
    );

    $phoneSpanish = $directory->default(
        location: Location::PuertoVallarta,
        locale: Locale::Spanish,
        device: Device::Mobile,
    );

    expect($phoneEnglish)->toBe($directory->mobilePhone)->and($phoneSpanish)->toBe($directory->default(
        location: Location::PuertoVallarta,
        locale: Locale::Spanish,
        device: Device::Mobile,
    ));
});

it('resolves a campaign phone number', function () {
    $directory = fakePhoneDirectory();

    $phone = $directory->campaign(
        location: Location::PuertoVallarta,
        locale: Locale::English,
        source: PhoneSource::Email,
    );

    $phone2 = $directory->campaign(
        location: Location::Cancun,
        locale: Locale::English,
        source: PhoneSource::Email,
    );

    $phone3 = $directory->campaign(
        location: Location::PuertoVallarta,
        locale: Locale::English,
        source: PhoneSource::Fads,
    );

    $phone4 = $directory->campaign(
        location: Location::PuertoVallarta,
        locale: Locale::Spanish,
        source: PhoneSource::Email,
    );

    expect($phone2)->toBeNull()
        ->and($phone)->toBe($directory->campaignPhone) 
        ->and($phone3)->toBeNull()
        ->and($phone4)->toBeNull();

});