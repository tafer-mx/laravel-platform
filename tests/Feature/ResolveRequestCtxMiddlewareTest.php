<?php

use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpFoundation\Response;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Middlewares\ResolveRequestCtx;

uses(TestCase::class);

it('resolves request context from request segments', function () {
    $requestCtx = new RequestCtx('garza-blanca');
    $middleware = new ResolveRequestCtx($requestCtx);
    $request = Request::create(
        '/es/puerto-vallarta/special-offers-and-packages/loyalty-sale',
        'GET',
        ['_storyblok' => '1'],
        [],
        [],
        [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ]
    );

    $response = $middleware->handle($request, fn () => new Response('ok'));

    expect($response->getContent())->toBe('ok')
        ->and($requestCtx->slug)->toBe('puerto-vallarta/special-offers-and-packages/loyalty-sale')
        ->and($requestCtx->isPreview)->toBeTrue()
        ->and($requestCtx->locale)->toBe(Locale::Spanish)
        ->and($requestCtx->location)->toBe(Location::PuertoVallarta)
        ->and($requestCtx->device)->toBe(Device::Mobile)
        ->and($requestCtx->device->isMobile())->toBeTrue();
});

it('applies the resolved locale and shares the request context with views', function () {
    $requestCtx = new RequestCtx('mousai');
    $middleware = new ResolveRequestCtx($requestCtx);
    $request = Request::create('/cancun/gallery');

    $middleware->handle($request, fn () => new Response('ok'));

    expect(app()->getLocale())->toBe('en')
        ->and(view()->shared('requestCtx'))->toBe($requestCtx)
        ->and($requestCtx->slug)->toBe('cancun/gallery')
        ->and($requestCtx->isPreview)->toBeFalse()
        ->and($requestCtx->locale)->toBe(Locale::English)
        ->and($requestCtx->location)->toBe(Location::Cancun);
});

it('does not allow the same request context to be resolved twice', function () {
    $requestCtx = new RequestCtx('garza-blanca');
    $middleware = new ResolveRequestCtx($requestCtx);

    $middleware->handle(
        Request::create('/puerto-vallarta/offers'),
        fn () => new Response('ok')
    );

    $middleware->handle(
        Request::create('/es/cancun/gallery'),
        fn () => new Response('ok')
    );
})->throws(LogicException::class, 'RequestCtx property [locale] has already been set.');
