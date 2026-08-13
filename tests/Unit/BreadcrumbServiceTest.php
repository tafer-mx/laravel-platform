<?php

use Illuminate\Http\Request;
use Storyblok\Api\Response\StoryResponse;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Contracts\StoryblokGateway;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Services\BreadcrumbService;
use TAFER\Core\Storyblok\StoryblokRequestFactory;

function breadcrumbContext(Locale $locale = Locale::English): RequestCtx
{
    return (new RequestCtx('villa-palmar-cancun'))
        ->setLocale($locale)
        ->setLocation(Location::Corp)
        ->setSlug('/')
        ->setIsPreview(false)
        ->setDevice(Device::Desktop);
}

it('generates English and Spanish breadcrumbs from public slugs', function () {
    $gateway = Mockery::mock(StoryblokGateway::class);
    $gateway->shouldNotReceive('getStoryByUuid');

    $english = new BreadcrumbService($gateway, new StoryblokRequestFactory, breadcrumbContext());
    $spanish = new BreadcrumbService(
        $gateway,
        new StoryblokRequestFactory,
        breadcrumbContext(Locale::Spanish),
    );

    expect($english->generateBreadcrumbs('cancun/dining/restaurant'))
        ->toBe([
            ['text' => 'Home', 'link' => '/cancun', 'icon' => 'home'],
            ['text' => 'Dining', 'link' => '/cancun/dining'],
            ['text' => 'Restaurant', 'link' => '/cancun/dining/restaurant'],
        ])
        ->and($spanish->generateBreadcrumbs('es/puerto-vallarta/residences'))
        ->toBe([
            ['text' => 'Inicio', 'link' => '/es/puerto-vallarta', 'icon' => 'home'],
            ['text' => 'Residencias', 'link' => '/es/puerto-vallarta/residences'],
        ]);
});

it('falls back to a CMS UUID when a URL breadcrumb cannot be generated', function () {
    app()->instance('request', Request::create('/'));

    $gateway = Mockery::mock(StoryblokGateway::class);
    $gateway->shouldReceive('getStoryByUuid')
        ->once()
        ->andReturn(new StoryResponse([
            'story' => ['full_slug' => 'brands/garza-blanca/cancun/dining'],
            'cv' => 1,
            'links' => [],
        ]));

    $service = new BreadcrumbService(
        $gateway,
        new StoryblokRequestFactory,
        breadcrumbContext(),
    );

    expect($service->generateBreadcrumbs('', [
        '11111111-1111-4111-8111-111111111111',
    ]))->not->toBeEmpty();
});

it('returns an empty result for invalid or excessively nested CMS overrides', function () {
    app()->instance('request', Request::create('/'));
    $gateway = Mockery::mock(StoryblokGateway::class);
    $gateway->shouldNotReceive('getStoryByUuid');
    $service = new BreadcrumbService($gateway, new StoryblokRequestFactory, breadcrumbContext());

    $nested = ['11111111-1111-4111-8111-111111111111'];

    for ($depth = 0; $depth < 6; $depth++) {
        $nested = [['brearcrum' => $nested]];
    }

    expect($service->generateBreadcrumbs('', [$nested]))->toBe([]);
});
