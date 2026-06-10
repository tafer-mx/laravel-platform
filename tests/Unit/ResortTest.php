<?php

use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;

it('has the expected resort slugs', function () {
    expect(Resort::GarzaBlanca->value)->toBe('garza-blanca')
        ->and(Resort::HotelMousai->value)->toBe('mousai')
        ->and(Resort::VillaPalmarCancun->value)->toBe('villa-palmar-cancun')
        ->and(Resort::Sanctuary->value)->toBe('sanctuary');
});

it('gets resort region codes', function () {
    expect(Resort::GarzaBlanca->regionCode(Location::Cancun))->toBe('GBCN')
        ->and(Resort::GarzaBlanca->regionCode(Location::PuertoVallarta))->toBe('GBPV')
        ->and(Resort::Sanctuary->regionCode(Location::PuertoVallarta))->toBe('SNCTRY')
        ->and(Resort::VillaPalmarCancun->regionCode(Location::Cabo))->toBeNull();
});

it('checks if a resort has a region', function () {
    expect(Resort::GarzaBlanca->hasRegion(Location::Cabo))->toBeTrue()
        ->and(Resort::VillaPalmarCancun->hasRegion(Location::Cabo))->toBeFalse()
        ->and(Resort::Sanctuary->hasRegion(Location::PuertoVallarta))->toBeTrue();
});

describe('Gets the region by a code', function () {
    it('GARZA BLANCA (GB)', function () {
        expect(Resort::resortByRegionCode('GBCN'))->toEqual(Resort::GarzaBlanca)
            ->and(Resort::resortByRegionCode('GBPV'))->toEqual(Resort::GarzaBlanca)
            ->and(Resort::resortByRegionCode('SNCTRY'))->toEqual(Resort::Sanctuary)
            ->and(Resort::resortByRegionCode('CABCODE'))->toBeNull();
    });

    it('Hotel Mousai (HM)', function () {
        expect(Resort::resortByRegionCode('MSCN'))->toEqual(Resort::HotelMousai)
            ->and(Resort::resortByRegionCode('MSPV'))->toEqual(Resort::HotelMousai);
    });

    it('Villa Palmar Cancun (VP)', function () {
        expect(Resort::resortByRegionCode('VPCN'))->toEqual(Resort::VillaPalmarCancun);
    });
});
