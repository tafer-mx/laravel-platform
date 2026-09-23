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

it('uses Sierra Lago public paths in both languages', function (string $source, string $path) {
    foreach ([Locale::Spanish, Locale::English] as $locale) {
        $this->app->instance(RequestCtx::class, (new RequestCtx('sierra-lago'))->setLocale($locale)->setLocation(Location::Corp));
        $expected = $locale === Locale::English ? '/en'.($path === '/' ? '' : $path) : $path;

        foreach (['cached_url' => $source, 'url' => $source] as $field => $value) {
            expect(StoryblokLinkResolver::resolve([$field => $value]))->toBe($expected);
        }
    }
})->with([
    'home' => ['/', '/'],
    'CMS home' => ['brands/sierra-lago/home-sierra-lago', '/'],
    'CMS page' => ['brands/sierra-lago/suites', '/suites'],
    'Spanish prefix' => ['/es/suites', '/suites'],
    'English prefix' => ['/en/suites', '/suites'],
    'repeated prefixes' => ['/en/es/en/suites', '/suites'],
    'query and fragment' => ['/es/suites?offer=summer#details', '/suites?offer=summer#details'],
    'complete segments only' => ['/escapadas/enjoy', '/escapadas/enjoy'],
]);

it('keeps non-page Sierra links unchanged', function (string $url) {
    $this->app->instance(RequestCtx::class, (new RequestCtx('sierra-lago'))->setLocale(Locale::English));

    expect(StoryblokLinkResolver::resolve(['url' => $url]))->toBe($url)
        ->and(StoryblokLinkResolver::resolve(['cached_url' => $url]))->toBe($url);
})->with([
    'https://example.com/es/suites', '//example.com/file.pdf', '#booking', '?offer=summer',
    'mailto:info@example.com', 'tel:+123456789', '/menus/dinner.pdf',
    '/api/contact/submit', '/download/pdf?url=menu.pdf', '/json/credits/resort.json',
]);

it('preserves both public languages for the other resorts', function (string $brand) {
    foreach ([Locale::English, Locale::Spanish] as $locale) {
        $this->app->instance(RequestCtx::class, (new RequestCtx($brand))->setLocale($locale));
        $path = '/suites';
        $expected = $locale === Locale::Spanish ? '/es/suites' : '/suites';

        expect(StoryblokLinkResolver::resolve(['cached_url' => "brands/{$brand}{$path}"]))
            ->toBe($expected)
            ->and(StoryblokLinkResolver::resolve(['url' => '/suites']))->toBe('/suites');
    }
})->with(['garza-blanca', 'mousai', 'villa-palmar-cancun']);

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
