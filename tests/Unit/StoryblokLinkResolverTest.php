<?php

use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;
use Storyblok\Api\Response\StoryResponse;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Contracts\StoryblokGateway;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Storyblok\StoryblokLinkResolver;
use TAFER\Core\Storyblok\StoryblokRequestFactory;

uses(TestCase::class);

beforeEach(function () {
    $context = (new RequestCtx('mousai'))
        ->setLocale(Locale::English)
        ->setLocation(Location::PuertoVallarta)
        ->setSlug('/')
        ->setIsPreview(false)
        ->setDevice(Device::Desktop);

    $this->app->instance(RequestCtx::class, $context);
    $this->app->instance(StoryblokRequestFactory::class, new StoryblokRequestFactory);
    StoryblokLinkResolver::setResolvedLinks([]);
    Cache::flush();
});

it('resolves anchors external urls and internal Storyblok urls by priority', function () {
    expect(StoryblokLinkResolver::resolve([
        'Section_id' => 'spa',
        'url' => 'https://example.com',
    ]))->toBe('#spa')
        ->and(StoryblokLinkResolver::resolve(['url' => 'http://example.com']))
        ->toBe('https://example.com')
        ->and(StoryblokLinkResolver::resolve([
            'story' => ['full_slug' => 'brands/mousai/puerto-vallarta/suites'],
        ]))->toBe('/puerto-vallarta/suites');
});

it('adds the Spanish locale prefix to internal links', function () {
    $context = (new RequestCtx('mousai'))
        ->setLocale(Locale::Spanish)
        ->setLocation(Location::PuertoVallarta)
        ->setSlug('/')
        ->setIsPreview(false)
        ->setDevice(Device::Desktop);
    $this->app->instance(RequestCtx::class, $context);

    expect(StoryblokLinkResolver::resolve([
        'cached_url' => 'brands/mousai/puerto-vallarta/suites',
    ]))->toBe('/es/puerto-vallarta/suites');
});

it('uses pre-resolved links before the UUID API fallback', function () {
    StoryblokLinkResolver::setResolvedLinks([
        'story-uuid' => ['url' => 'brands/mousai/cancun/gallery'],
    ]);

    expect(StoryblokLinkResolver::resolve([
        'id' => 'story-uuid',
        'linktype' => 'story',
    ]))->toBe('/cancun/gallery');
});

it('resolves and caches UUID fallbacks through StoryblokGateway', function () {
    $uuid = '11111111-1111-4111-8111-111111111111';
    $gateway = Mockery::mock(StoryblokGateway::class);
    $gateway->shouldReceive('getStoryByUuid')
        ->once()
        ->andReturn(new StoryResponse([
            'story' => ['full_slug' => 'brands/mousai/puerto-vallarta/experiences'],
            'cv' => 1,
            'links' => [],
        ]));
    $this->app->instance(StoryblokGateway::class, $gateway);

    $link = ['id' => $uuid, 'linktype' => 'story'];

    expect(StoryblokLinkResolver::resolve($link, 'en'))
        ->toBe('/puerto-vallarta/experiences')
        ->and(Cache::get("storyblok_link_{$uuid}_en"))
        ->toBe('/puerto-vallarta/experiences');
});
