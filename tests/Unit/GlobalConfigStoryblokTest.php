<?php

use Orchestra\Testbench\TestCase;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Storyblok\LoadsGlobalConfig;

uses(TestCase::class);

function globalConfigContext(string $resort, Location $location, Locale $locale = Locale::English): RequestCtx
{
    return (new RequestCtx($resort))
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

    $service->getGlobalConfig(globalConfigContext('villa-palmar-cancun', Location::Cancun));

    expect($service->slug)->toBe('brands/villa-palmar-cancun/config_brand');
});
