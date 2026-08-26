<?php

use Orchestra\Testbench\TestCase;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Storyblok\LoadsGlobalConfig;

uses(TestCase::class);

function globalConfigContext(
    string $resort,
    Location $location,
    Locale $locale = Locale::English,
    ?Resort $childResort = null,
): RequestCtx {
    return (new RequestCtx($resort))
        ->setChildResort($childResort)
        ->setLocation($location)
        ->setLocale($locale);
}

it('loads a location-scoped global configuration through the consumer Storyblok service', function () {
    config(['tafer.storyblok.global_config.location_scoped' => true]);

    $service = new class
    {
        use LoadsGlobalConfig;

        public array $arguments = [];

        public function getStory(string $slug, bool $isPreview, string $locale): ?array
        {
            $this->arguments = compact('slug', 'isPreview', 'locale');

            return ['full_slug' => $slug];
        }
    };

    $result = $service->getGlobalConfig(
        globalConfigContext('garza-blanca', Location::PuertoVallarta, Locale::Spanish),
        true,
    );

    expect($result)->toBe([
        'full_slug' => 'brands/garza-blanca/puerto-vallarta/config_brand_puerto-vallarta',
    ])->and($service->arguments)->toBe([
        'slug' => 'brands/garza-blanca/puerto-vallarta/config_brand_puerto-vallarta',
        'isPreview' => true,
        'locale' => 'es',
    ]);
});

it('loads a brand global configuration for corporate requests', function () {
    config(['tafer.storyblok.global_config.location_scoped' => true]);

    $service = new class
    {
        use LoadsGlobalConfig;

        public string $slug = '';

        public function getStory(string $slug, bool $isPreview, string $locale): ?array
        {
            $this->slug = $slug;

            return [];
        }
    };

    $service->getGlobalConfig(globalConfigContext('mousai', Location::Corp));

    expect($service->slug)->toBe('brands/mousai/config_brand');
});

it('loads a child resort global configuration under the parent brand and location', function () {
    config(['tafer.storyblok.global_config.location_scoped' => true]);

    $service = new class
    {
        use LoadsGlobalConfig;

        public array $arguments = [];

        public function getStory(string $slug, bool $isPreview, string $locale): ?array
        {
            $this->arguments = compact('slug', 'isPreview', 'locale');

            return ['full_slug' => $slug];
        }
    };

    $result = $service->getGlobalConfig(
        globalConfigContext(
            'garza-blanca',
            Location::PuertoVallarta,
            Locale::Spanish,
            Resort::Sanctuary,
        ),
        true,
    );

    expect($result)->toBe([
        'full_slug' => 'brands/garza-blanca/puerto-vallarta/sanctuary/config_brand_puerto-vallarta-sanctuary',
    ])->and($service->arguments)->toBe([
        'slug' => 'brands/garza-blanca/puerto-vallarta/sanctuary/config_brand_puerto-vallarta-sanctuary',
        'isPreview' => true,
        'locale' => 'es',
    ]);
});

it('ignores child resorts that do not belong to the parent brand or location', function (
    string $resort,
    Location $location,
) {
    config(['tafer.storyblok.global_config.location_scoped' => true]);

    $service = new class
    {
        use LoadsGlobalConfig;

        public string $slug = '';

        public function getStory(string $slug, bool $isPreview, string $locale): ?array
        {
            $this->slug = $slug;

            return [];
        }
    };

    $service->getGlobalConfig(globalConfigContext(
        $resort,
        $location,
        childResort: Resort::Sanctuary,
    ));

    expect($service->slug)->toBe(
        "brands/{$resort}/{$location->value}/config_brand_{$location->value}",
    );
})->with([
    'different parent resort' => ['mousai', Location::PuertoVallarta],
    'unsupported child location' => ['garza-blanca', Location::Cancun],
]);

it('supports resorts whose global configuration is always brand-scoped', function () {
    config(['tafer.storyblok.global_config.location_scoped' => false]);

    $service = new class
    {
        use LoadsGlobalConfig;

        public string $slug = '';

        public function getStory(string $slug, bool $isPreview, string $locale): ?array
        {
            $this->slug = $slug;

            return [];
        }
    };

    $service->getGlobalConfig(globalConfigContext(
        'villa-palmar-cancun',
        Location::Cancun,
        childResort: Resort::Sanctuary,
    ));

    expect($service->slug)->toBe('brands/villa-palmar-cancun/config_brand');
});
