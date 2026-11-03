<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Orchestra\Testbench\TestCase;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Contracts\PhoneResolver;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Phones\ConfigPhoneResolver;
use TAFER\Core\TAFERServiceProvider;

uses(TestCase::class);

function phoneRequest(array $query = [], array $cookies = []): Request
{
    return Request::create('/', 'GET', $query, $cookies);
}

function phone(string $number, string $text): array
{
    return ['url' => "tel:{$number}", 'button_text' => $text];
}

/**
 * Sitio con inglés como idioma principal (como Villa Palmar): partners en inglés.
 */
function englishSiteMap(): array
{
    return [
        'en' => [
            'desktop' => phone('+18009314608', '+1 800 931 4608'),
            'mobile' => phone('+18449257740', '+1 844 925 7740'),
            '8926' => phone('+16054035776', '+1 605 403 5776'),
        ],
        'es' => [
            'desktop' => phone('+528003230763', '+52 800 323 0763'),
            'mobile' => phone('+528006490442', '+52 800 649 0442'),
            '8926' => phone('+520000000000', '+52 000 000 0000'),
        ],
    ];
}

/**
 * Sitio con español como idioma principal (como Sierra Lago): partners en español.
 */
function spanishSiteMap(): array
{
    return [
        'es' => [
            'desktop' => phone('+528003230581', '800 323 0581'),
            'mobile' => phone('+528006490478', '800 649 0478'),
            '8926' => phone('+523324512396', '33 2451 2396'),
        ],
        'en' => [
            'desktop' => phone('+18009316063', '+1 800 931 6063'),
            'mobile' => phone('+18449330612', '+1 844 933 0612'),
        ],
    ];
}

function phoneResolver(array $config = []): PhoneResolver
{
    config($config);
    app()->forgetScopedInstances();

    return app(PhoneResolver::class);
}

beforeEach(function () {
    $this->app->register(TAFERServiceProvider::class);
});

it('binds the contract to the config resolver', function () {
    expect(phoneResolver(['tollfree' => englishSiteMap()]))->toBeInstanceOf(ConfigPhoneResolver::class);
});

describe('English as default locale (package default)', function () {
    beforeEach(function () {
        $this->resolver = phoneResolver(['tollfree' => englishSiteMap()]);
    });

    it('resolves numbers by locale and device, treating tablet as mobile', function () {
        expect($this->resolver->resolve(phoneRequest(), Locale::English, Device::Desktop)->url)->toBe('tel:+18009314608')
            ->and($this->resolver->resolve(phoneRequest(), Locale::English, Device::Tablet)->url)->toBe('tel:+18449257740')
            ->and($this->resolver->resolve(phoneRequest(), Locale::Spanish, Device::Mobile)->toArray())
            ->toBe(['url' => 'tel:+528006490442', 'button_text' => '+52 800 649 0442']);
    });

    it('applies partner numbers only in English, regardless of device', function () {
        expect($this->resolver->resolve(phoneRequest(['partner' => '8926']), Locale::English, Device::Mobile)->url)
            ->toBe('tel:+16054035776')
            ->and($this->resolver->resolve(phoneRequest(['partner' => '8926']), Locale::Spanish, Device::Desktop)->url)
            ->toBe('tel:+528003230763');
    });
});

describe('Spanish as default locale', function () {
    beforeEach(function () {
        $this->resolver = phoneResolver([
            'tollfree' => spanishSiteMap(),
            'tafer.storyblok.default_locale' => 'es',
        ]);
    });

    it('applies partner numbers only in Spanish', function () {
        expect($this->resolver->resolve(phoneRequest(['partner' => '8926']), Locale::Spanish, Device::Mobile)->url)
            ->toBe('tel:+523324512396')
            ->and($this->resolver->resolve(phoneRequest(['partner' => '8926']), Locale::English, Device::Mobile)->url)
            ->toBe('tel:+18449330612');
    });

    it('falls back to the device number for unknown or invalid partners', function () {
        expect($this->resolver->resolve(phoneRequest(['partner' => '12345']), Locale::Spanish, Device::Desktop)->url)
            ->toBe('tel:+528003230581')
            ->and($this->resolver->resolve(phoneRequest(['partner' => 'abc']), Locale::Spanish, Device::Desktop)->url)
            ->toBe('tel:+528003230581');
    });

    it('returns the desktop number as the base phone of each locale', function () {
        expect($this->resolver->basePhone(Locale::Spanish)->url)->toBe('tel:+528003230581')
            ->and($this->resolver->basePhone(Locale::English)->url)->toBe('tel:+18009316063');
    });
});

it('uses tafer.phones.default_locale over the Storyblok default locale', function () {
    $resolver = phoneResolver([
        'tollfree' => spanishSiteMap(),
        'tafer.storyblok.default_locale' => 'en',
        'tafer.phones.default_locale' => 'es',
    ]);

    expect($resolver->resolve(phoneRequest(['partner' => '8926']), Locale::Spanish, Device::Desktop)->url)
        ->toBe('tel:+523324512396');
});

it('applies partner numbers in every configured partner locale', function () {
    $resolver = phoneResolver([
        'tollfree' => englishSiteMap(),
        'tafer.phones.partner_locales' => ['en', 'es'],
    ]);

    expect($resolver->resolve(phoneRequest(['partner' => '8926']), Locale::Spanish, Device::Desktop)->url)
        ->toBe('tel:+520000000000');
});

it('falls back to the default locale when a locale has no numbers', function () {
    $resolver = phoneResolver(['tollfree' => ['en' => englishSiteMap()['en']]]);

    expect($resolver->resolve(phoneRequest(), Locale::Spanish, Device::Desktop)->url)->toBe('tel:+18009314608')
        ->and($resolver->basePhone(Locale::Spanish)->url)->toBe('tel:+18009314608');
});

it('returns null when the site has no phone map', function () {
    $resolver = phoneResolver(['tollfree' => null]);

    expect($resolver->resolve(phoneRequest(), Locale::English, Device::Desktop))->toBeNull()
        ->and($resolver->basePhone(Locale::English))->toBeNull();
});

it('reads the phone map from the configured key', function () {
    $resolver = phoneResolver([
        'tafer.phones.config_key' => 'site.phones',
        'site.phones' => ['en' => ['desktop' => phone('+10000000000', '+1 000 000 0000')]],
    ]);

    expect($resolver->resolve(phoneRequest(), Locale::English, Device::Desktop)->url)->toBe('tel:+10000000000');
});

describe('partner cookie', function () {
    beforeEach(function () {
        $this->resolver = phoneResolver(['tollfree' => englishSiteMap()]);
    });

    it('reads the partner from the cookie when the url has none', function () {
        $phone = $this->resolver->resolve(phoneRequest([], ['partner_id' => '8926']), Locale::English, Device::Desktop);

        expect($phone->url)->toBe('tel:+16054035776')
            ->and(Cookie::hasQueued('partner_id'))->toBeFalse();
    });

    it('gives priority to the url partner and stores it for 30 days', function () {
        $this->resolver->resolve(phoneRequest(['partner' => '8926'], ['partner_id' => '8929']), Locale::English, Device::Desktop);

        $cookie = Cookie::queued('partner_id');

        expect($cookie->getValue())->toBe('8926')
            ->and((int) round(($cookie->getExpiresTime() - time()) / 60))->toBe(43200);
    });

    it('stores the partner even on locales without partner numbers', function () {
        $this->resolver->resolve(phoneRequest(['partner' => '8926']), Locale::Spanish, Device::Desktop);

        expect(Cookie::queued('partner_id')->getValue())->toBe('8926');
    });
});

describe('location scoped sites', function () {
    beforeEach(function () {
        $this->resolver = phoneResolver([
            'tafer.phones.location_scoped' => true,
            'tollfree' => [
                'cancun' => englishSiteMap(),
                'puerto-vallarta' => ['en' => ['desktop' => phone('+18001112222', '+1 800 111 2222')]],
            ],
        ]);
    });

    it('resolves numbers by location with one partner cookie per location', function () {
        $phone = $this->resolver->resolve(phoneRequest(['partner' => '8926']), Locale::English, Device::Mobile, Location::Cancun);

        expect($phone->url)->toBe('tel:+16054035776')
            ->and(Cookie::queued('partner_id_cancun')->getValue())->toBe('8926')
            ->and($this->resolver->basePhone(Locale::English, Location::PuertoVallarta)->url)->toBe('tel:+18001112222');
    });

    it('returns null for locations without numbers', function () {
        expect($this->resolver->resolve(phoneRequest(), Locale::English, Device::Desktop, Location::Cabo))->toBeNull()
            ->and($this->resolver->resolve(phoneRequest(), Locale::English, Device::Desktop))->toBeNull();
    });

    it('resolves the number from the request context', function () {
        $context = (new RequestCtx('garza-blanca'))
            ->setLocale(Locale::English)
            ->setLocation(Location::Cancun)
            ->setDevice(Device::Mobile);

        expect($this->resolver->forContext(phoneRequest(), $context)->url)->toBe('tel:+18449257740');
    });
});

/**
 * Mapa de números con un horario fuera de horario de oficina (como Bookline).
 */
function scheduledSiteMap(array $schedule = []): array
{
    return [
        ...spanishSiteMap(),
        'schedules' => [
            'bookline' => [
                'from' => '23:00',
                'to' => '06:00',
                'timezone' => 'America/Mexico_City',
                'phones' => [
                    'es' => phone('+523229800519', '322 980 0519'),
                    'en' => phone('+17252818275', '+1 725 281 8275'),
                ],
                ...$schedule,
            ],
        ],
    ];
}

describe('time-based schedules', function () {
    beforeEach(function () {
        $this->resolver = phoneResolver([
            'tollfree' => scheduledSiteMap(),
            'tafer.storyblok.default_locale' => 'es',
            'tafer.phones.schedules.allow_time_override' => false,
        ]);
    });

    it('uses the schedule numbers inside the window, crossing midnight', function (string $time) {
        $this->travelTo(Carbon\Carbon::parse("2026-11-02 {$time}", 'America/Mexico_City'));

        expect($this->resolver->resolve(phoneRequest(), Locale::Spanish, Device::Mobile)->url)->toBe('tel:+523229800519')
            ->and($this->resolver->resolve(phoneRequest(), Locale::English, Device::Desktop)->url)->toBe('tel:+17252818275');
    })->with(['23:00', '23:59', '00:00', '03:15', '05:59']);

    it('uses the regular numbers outside the window', function (string $time) {
        $this->travelTo(Carbon\Carbon::parse("2026-11-02 {$time}", 'America/Mexico_City'));

        expect($this->resolver->resolve(phoneRequest(), Locale::Spanish, Device::Mobile)->url)->toBe('tel:+528006490478')
            ->and($this->resolver->resolve(phoneRequest(), Locale::English, Device::Desktop)->url)->toBe('tel:+18009316063');
    })->with(['06:00', '12:00', '22:59']);

    it('evaluates the window in the schedule timezone, not the app timezone', function () {
        // 05:30 UTC = 23:30 del día anterior en Ciudad de México (UTC-6).
        $this->travelTo(Carbon\Carbon::parse('2026-11-03 05:30', 'UTC'));

        expect($this->resolver->resolve(phoneRequest(), Locale::Spanish, Device::Desktop)->url)->toBe('tel:+523229800519');
    });

    it('wins over partner numbers but keeps tracking the partner', function () {
        $this->travelTo(Carbon\Carbon::parse('2026-11-02 01:00', 'America/Mexico_City'));

        expect($this->resolver->resolve(phoneRequest(['partner' => '8926']), Locale::Spanish, Device::Desktop)->url)
            ->toBe('tel:+523229800519')
            ->and(Cookie::queued('partner_id')->getValue())->toBe('8926');
    });

    it('does not change the base phone used to detect swappable numbers', function () {
        $this->travelTo(Carbon\Carbon::parse('2026-11-02 01:00', 'America/Mexico_City'));

        expect($this->resolver->basePhone(Locale::Spanish)->url)->toBe('tel:+528003230581');
    });

    it('ignores the time override when it is not allowed', function () {
        $this->travelTo(Carbon\Carbon::parse('2026-11-02 12:00', 'America/Mexico_City'));

        expect($this->resolver->resolve(phoneRequest(['phone_time' => '23:30']), Locale::Spanish, Device::Desktop)->url)
            ->toBe('tel:+528003230581');
    });
});

it('lets QA force the schedule time when the override is allowed', function () {
    $this->travelTo(Carbon\Carbon::parse('2026-11-02 12:00', 'America/Mexico_City'));

    $resolver = phoneResolver([
        'tollfree' => scheduledSiteMap(),
        'tafer.phones.schedules.allow_time_override' => true,
    ]);

    expect($resolver->resolve(phoneRequest(['phone_time' => '23:30']), Locale::English, Device::Desktop)->url)
        ->toBe('tel:+17252818275')
        ->and($resolver->resolve(phoneRequest(['phone_time' => '07:00']), Locale::English, Device::Desktop)->url)
        ->toBe('tel:+18009316063')
        ->and($resolver->resolve(phoneRequest(['phone_time' => 'abc']), Locale::English, Device::Desktop)->url)
        ->toBe('tel:+18009316063');
});

it('allows the time override by default only in the local environment', function (string $environment, string $expected) {
    $this->travelTo(Carbon\Carbon::parse('2026-11-02 12:00', 'America/Mexico_City'));
    $this->app['env'] = $environment;

    $resolver = phoneResolver([
        'tollfree' => scheduledSiteMap(),
        'tafer.phones.schedules.allow_time_override' => null,
    ]);

    expect($resolver->resolve(phoneRequest(['phone_time' => '01:55']), Locale::English, Device::Desktop)->url)
        ->toBe($expected);
})->with([
    'local' => ['local', 'tel:+17252818275'],
    'production' => ['production', 'tel:+18009316063'],
    'staging' => ['staging', 'tel:+18009316063'],
]);

it('uses the configured site timezone when the schedule has none', function () {
    // 12:00 UTC = 06:00 en Ciudad de México: fuera de la ventana; en UTC estaría dentro de 11:00–13:00.
    $this->travelTo(Carbon\Carbon::parse('2026-11-02 12:00', 'UTC'));

    $resolver = phoneResolver([
        'tollfree' => scheduledSiteMap(['timezone' => null, 'from' => '11:00', 'to' => '13:00']),
        'tafer.phones.schedules.timezone' => 'America/Mexico_City',
    ]);

    expect($resolver->resolve(phoneRequest(), Locale::English, Device::Desktop)->url)->toBe('tel:+18009316063');
});

it('skips disabled schedules and schedules without a number for the locale', function () {
    $this->travelTo(Carbon\Carbon::parse('2026-11-02 01:00', 'America/Mexico_City'));

    $disabled = phoneResolver(['tollfree' => scheduledSiteMap(['enabled' => false])]);
    $spanishOnly = phoneResolver(['tollfree' => scheduledSiteMap([
        'phones' => ['es' => phone('+523229800519', '322 980 0519')],
    ])]);

    expect($disabled->resolve(phoneRequest(), Locale::Spanish, Device::Desktop)->url)->toBe('tel:+528003230581')
        ->and($spanishOnly->resolve(phoneRequest(), Locale::English, Device::Desktop)->url)->toBe('tel:+18009316063');
});

it('resolves schedules per location on location scoped sites', function () {
    $this->travelTo(Carbon\Carbon::parse('2026-11-02 01:00', 'America/Mexico_City'));

    $resolver = phoneResolver([
        'tafer.phones.location_scoped' => true,
        'tollfree' => [
            'cancun' => scheduledSiteMap(),
            'puerto-vallarta' => englishSiteMap(),
        ],
    ]);

    expect($resolver->resolve(phoneRequest(), Locale::English, Device::Desktop, Location::Cancun)->url)->toBe('tel:+17252818275')
        ->and($resolver->resolve(phoneRequest(), Locale::English, Device::Desktop, Location::PuertoVallarta)->url)->toBe('tel:+18009314608');
});
