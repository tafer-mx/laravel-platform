<?php
    use TAFER\Core\Enums\Location;
    use TAFER\Core\Enums\Locale;

    it('has the expected location slugs', function () {
        expect(Location::Cancun->value)->toBe('cancun')
            ->and(Location::PuertoVallarta->value)->toBe('puerto-vallarta')
            ->and(Location::Cabo->value)->toBe('los-cabos')
            ->and(Location::Corp->value)->toBe('corp');
    });

    it('gets translated location labels', function () {
        expect(Location::Cancun->label(Locale::English))->toBe('Cancun')
            ->and(Location::Cancun->label(Locale::Spanish))->toBe('Cancún')
            ->and(Location::Corp->label(Locale::English))->toBe('Corporate')
            ->and(Location::Corp->label(Locale::Spanish))->toBe('Corporativo');
    });

    it('creates location from valid slug', function () {
        expect(Location::fromSlug('cancun'))->toBe(Location::Cancun)
            ->and(Location::fromSlug('puerto-vallarta'))->toBe(Location::PuertoVallarta);
    });

    it('returns null from invalid location slug', function () {
        expect(Location::fromSlug(null))->toBeNull()
            ->and(Location::fromSlug('invalid-location'))->toBeNull();
    });

    it('detects corporate location', function () {
        expect(Location::Corp->isCorp())->toBeTrue()
            ->and(Location::Cancun->isCorp())->toBeFalse();
    });