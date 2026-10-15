<?php

use Eris\Generators;
use Eris\TestTrait;
use GuzzleHttp\Client;
use Orchestra\Testbench\TestCase;
use Storyblok\Api\StoryblokClientInterface;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Contracts\ReviewClient;
use TAFER\Core\Services\ReviewsService;
use TAFER\Core\TAFERServiceProvider;

abstract class PackageConfigPropertyTestCase extends TestCase
{
    use TestTrait;
}

uses(PackageConfigPropertyTestCase::class);

beforeEach(function () {
    $this->app->register(TAFERServiceProvider::class);
});

it('requires a brand slug to resolve the request context', function () {
    config(['tafer.brand.slug' => '']);

    app(RequestCtx::class);
})->throws(InvalidArgumentException::class, 'Missing required config value [tafer.brand.slug].');

it('requires a Storyblok token to resolve the Storyblok client', function () {
    config(['tafer.storyblok.token' => '']);

    app(StoryblokClientInterface::class);
})->throws(InvalidArgumentException::class, 'Missing required config value [tafer.storyblok.token].');

it('configures the Storyblok client URL token and timeout from config', function () {
    config([
        'tafer.storyblok.base_uri' => 'https://api.storyblok.test',
        'tafer.storyblok.token' => 'public-token',
        'tafer.storyblok.timeout' => 9,
    ]);

    $client = app(StoryblokClientInterface::class);

    $token = new ReflectionProperty($client, 'token');
    $httpClient = (new ReflectionProperty($client, 'client'))->getValue($client);
    $defaultOptions = (new ReflectionProperty($httpClient, 'defaultOptionsByRegexp'))->getValue($httpClient);

    expect($token->getValue($client))->toBe('public-token')
        ->and($defaultOptions)->toHaveKey('https\://api\.storyblok\.test/')
        ->and($defaultOptions['https\://api\.storyblok\.test/']['base_uri'])->toBe('https://api.storyblok.test')
        ->and($defaultOptions['https\://api\.storyblok\.test/']['timeout'])->toBe(9);
});

it('requires a middleware base URL to resolve the reviews client', function () {
    config(['tafer.middleware.base_url' => '']);

    app(ReviewClient::class);
})->throws(InvalidArgumentException::class, 'Missing required config value [tafer.middleware.base_url].');

it('uses the shared middleware base URL by default', function () {
    /** @var ReviewsService $service */
    $service = app(ReviewClient::class);

    $reflection = new ReflectionProperty($service, 'client');
    $client = $reflection->getValue($service);

    expect($client)->toBeInstanceOf(Client::class)
        ->and((string) $client->getConfig('base_uri'))->toBe('https://middleware.taferresorts.com/');
});

it('provides the complete geographic configuration defaults', function () {
    expect(config('tafer.geo'))->toBe([
        'header' => 'CF-IPCountry',
        'allow_override' => env('GEO_ALLOW_OVERRIDE', env('APP_DEBUG', false)),
        'override_query_param' => 'country',
    ]);
});

it('completes a partial geographic override without replacing sibling options', function () {
    config([
        'tafer' => [
            'brand' => [
                'slug' => 'consumer-brand',
            ],
            'geo' => [
                'header' => 'X-Visitor-Country',
            ],
            'consumer_option' => [
                'enabled' => true,
            ],
        ],
    ]);

    (new TAFERServiceProvider($this->app))->register();

    expect(config('tafer.geo'))->toBe([
        'header' => 'X-Visitor-Country',
        'allow_override' => env('GEO_ALLOW_OVERRIDE', env('APP_DEBUG', false)),
        'override_query_param' => 'country',
    ])->and(config('tafer.brand.slug'))->toBe('consumer-brand')
        ->and(config('tafer.consumer_option.enabled'))->toBeTrue()
        ->and(config('tafer.middleware.base_url'))->toBe('https://middleware.taferresorts.com/');
});

it('does not register a geographic route automatically', function () {
    $geographicRoutes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array($route->uri(), ['geo', 'api/geo'], true));

    expect($geographicRoutes)->toBeEmpty();
});

function geographicOverridesGenerator()
{
    return Generators::map(
        function (array $values): array {
            [$overrideHeader, $header, $overrideAllow, $allow, $overrideParam, $param] = $values;
            $overrides = [];

            if ($overrideHeader) {
                $overrides['header'] = $header;
            }

            if ($overrideAllow) {
                $overrides['allow_override'] = $allow;
            }

            if ($overrideParam) {
                $overrides['override_query_param'] = $param;
            }

            return $overrides;
        },
        Generators::tuple(
            Generators::bool(),
            Generators::elements(['X-Country', 'X-Visitor-Country', 'CF-Country-Code']),
            Generators::bool(),
            Generators::bool(),
            Generators::bool(),
            Generators::elements(['country', 'qa_country', 'visitor_country']),
        ),
    );
}

// Feature: shared-visitor-geolocation, Property 6: Composición no destructiva de configuración
// Validates: Requirements 4.6
it('property 6 preserves every geographic override and completes omitted package defaults', function () {
    $defaults = config('tafer.geo');

    $this->limitTo(100)
        ->forAll(geographicOverridesGenerator())
        ->then(function (array $overrides) use ($defaults): void {
            config(['tafer' => ['geo' => $overrides]]);

            (new TAFERServiceProvider($this->app))->register();

            expect(config('tafer.geo'))->toBe(array_replace($defaults, $overrides));
        });
});
