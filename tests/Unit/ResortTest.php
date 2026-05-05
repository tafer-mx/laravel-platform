<?php
    use TAFER\Core\Enums\Resort;
    use TAFER\Core\Enums\Location;

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