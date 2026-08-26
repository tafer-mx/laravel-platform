<?php

use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpFoundation\Response;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Context\RequestCtxRelation;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Middlewares\ResolveRequestCtx;
use TAFER\Core\Services\StoryblokVariableResolver;

uses(TestCase::class);

it('resolves request context from request segments', function () {
    $requestCtx = new RequestCtx('garza-blanca');
    $ctxRelation = new RequestCtxRelation;
    $variableResolver = new StoryblokVariableResolver;
    $middleware = new ResolveRequestCtx($requestCtx, $ctxRelation, $variableResolver);
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
        ->and($requestCtx->childResort)->toBeNull()
        ->and($requestCtx->effectiveResort())->toBe(Resort::GarzaBlanca)
        ->and($requestCtx->slug)->toBe('puerto-vallarta/special-offers-and-packages/loyalty-sale')
        ->and($requestCtx->isPreview)->toBeTrue()
        ->and($requestCtx->locale)->toBe(Locale::Spanish)
        ->and($requestCtx->location)->toBe(Location::PuertoVallarta)
        ->and($requestCtx->device)->toBe(Device::Mobile)
        ->and($requestCtx->device->isMobile())->toBeTrue();
});

it('applies the resolved locale and shares the request context with views', function () {
    $requestCtx = new RequestCtx('mousai');
    $ctxRelation = new RequestCtxRelation;
    $variableResolver = new StoryblokVariableResolver;
    $middleware = new ResolveRequestCtx($requestCtx, $ctxRelation, $variableResolver);
    $request = Request::create('/cancun/gallery');

    $middleware->handle($request, fn () => new Response('ok'));

    expect(app()->getLocale())->toBe('en')
        ->and(view()->shared('requestCtx'))->toBe($requestCtx)
        ->and(view()->shared('ctxRelation'))->toBe($ctxRelation)
        ->and(view()->shared('variableResolver'))->toBe($variableResolver)
        ->and($requestCtx->slug)->toBe('cancun/gallery')
        ->and($requestCtx->isPreview)->toBeFalse()
        ->and($requestCtx->locale)->toBe(Locale::English)
        ->and($requestCtx->location)->toBe(Location::Cancun);
});

it('resolves sanctuary as a child resort while preserving the parent Storyblok path', function (string $path) {
    $requestCtx = new RequestCtx('garza-blanca');
    $middleware = new ResolveRequestCtx(
        $requestCtx,
        new RequestCtxRelation,
        new StoryblokVariableResolver,
    );

    $middleware->handle(Request::create($path), fn () => new Response('ok'));

    expect($requestCtx->resort)->toBe(Resort::GarzaBlanca)
        ->and($requestCtx->childResort)->toBe(Resort::Sanctuary)
        ->and($requestCtx->effectiveResort())->toBe(Resort::Sanctuary)
        ->and($requestCtx->location)->toBe(Location::PuertoVallarta)
        ->and($requestCtx->slug)->toBe('puerto-vallarta/sanctuary/suites')
        ->and($requestCtx->storyblokSlug())
        ->toBe('brands/garza-blanca/puerto-vallarta/sanctuary/suites');
})->with([
    '/puerto-vallarta/sanctuary/suites',
    '/es/puerto-vallarta/sanctuary/suites',
    '/en/puerto-vallarta/sanctuary/suites',
]);

it('does not resolve sanctuary under a different parent resort', function () {
    $requestCtx = new RequestCtx('mousai');
    $middleware = new ResolveRequestCtx(
        $requestCtx,
        new RequestCtxRelation,
        new StoryblokVariableResolver,
    );

    $middleware->handle(
        Request::create('/puerto-vallarta/sanctuary/suites'),
        fn () => new Response('ok'),
    );

    expect($requestCtx->resort)->toBe(Resort::HotelMousai)
        ->and($requestCtx->childResort)->toBeNull()
        ->and($requestCtx->effectiveResort())->toBe(Resort::HotelMousai);
});

it('does not allow the same request context to be resolved twice', function () {
    $requestCtx = new RequestCtx('garza-blanca');
    $ctxRelation = new RequestCtxRelation;
    $variableResolver = new StoryblokVariableResolver;
    $middleware = new ResolveRequestCtx($requestCtx, $ctxRelation, $variableResolver);

    $middleware->handle(
        Request::create('/puerto-vallarta/offers'),
        fn () => new Response('ok')
    );

    $middleware->handle(
        Request::create('/es/cancun/gallery'),
        fn () => new Response('ok')
    );
})->throws(LogicException::class, 'RequestCtx property [childResort] has already been set.');
