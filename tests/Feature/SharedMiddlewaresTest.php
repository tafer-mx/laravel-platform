<?php

use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Middlewares\RedirectLegacyHomePrefix;
use TAFER\Core\Middlewares\SetCacheHeaders;

uses(TestCase::class);

function sharedMiddlewareContext(
    string $brand = 'mousai',
    Locale $locale = Locale::English,
    Location $location = Location::PuertoVallarta,
    string $slug = 'puerto-vallarta/suites',
    bool $preview = false,
): RequestCtx {
    return (new RequestCtx($brand))
        ->setLocale($locale)
        ->setLocation($location)
        ->setSlug($slug)
        ->setIsPreview($preview)
        ->setDevice(Device::Desktop);
}

it('adds public cache headers and appends the user agent vary value to html responses', function () {
    $middleware = new SetCacheHeaders(sharedMiddlewareContext());
    $request = Request::create('/puerto-vallarta/suites');

    $response = $middleware->handle($request, fn () => new Response(
        '<html></html>',
        200,
        ['Content-Type' => 'text/html', 'Vary' => 'Accept-Encoding'],
    ));

    expect($response->headers->get('Cache-Control'))->toBe('max-age=300, public')
        ->and($response->headers->all('Vary'))->toBe(['Accept-Encoding', 'User-Agent']);
});

it('disables caches for Storyblok preview requests', function () {
    $middleware = new SetCacheHeaders(sharedMiddlewareContext(preview: true));

    $response = $middleware->handle(
        Request::create('/puerto-vallarta/suites'),
        fn () => new Response('<html></html>', 200, ['Content-Type' => 'text/html']),
    );

    expect($response->headers->get('Cache-Control'))
        ->toContain('max-age=0')
        ->toContain('must-revalidate')
        ->toContain('no-cache')
        ->toContain('no-store');
});

it('does not add public cache headers to excluded responses', function (string $uri, string $method, array $headers) {
    $middleware = new SetCacheHeaders(sharedMiddlewareContext());
    $response = $middleware->handle(
        Request::create($uri, $method, server: $headers),
        fn () => new Response('ok', 200, ['Content-Type' => 'application/json']),
    );

    expect($response->headers->get('Cache-Control'))->not->toContain('public')
        ->not->toContain('max-age=300');
})->with([
    'post' => ['/puerto-vallarta/suites', 'POST', []],
    'json' => ['/puerto-vallarta/suites', 'GET', ['HTTP_ACCEPT' => 'application/json']],
    'download' => ['/download/menu.pdf', 'GET', []],
]);

it('returns gone for public Storyblok brand paths', function () {
    $middleware = new RedirectLegacyHomePrefix(sharedMiddlewareContext());

    try {
        $middleware->handle(Request::create('/brands/mousai'), fn () => new Response('ok'));
        $this->fail('Expected a 410 response.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(410);
    }
});

it('redirects explicit English paths to their canonical public path', function () {
    $context = sharedMiddlewareContext(slug: 'hello-world', location: Location::Corp);
    $middleware = new RedirectLegacyHomePrefix($context);

    $response = $middleware->handle(
        Request::create('/en/hello-world'),
        fn () => new Response('ok'),
    );

    expect($response->getTargetUrl())->toEndWith('/hello-world');
});

it('rejects legacy home prefixes', function () {
    $context = sharedMiddlewareContext(slug: 'puerto-vallarta/home-puerto-vallarta');
    $middleware = new RedirectLegacyHomePrefix($context);

    try {
        $middleware->handle(
            Request::create('/puerto-vallarta/home-puerto-vallarta'),
            fn () => new Response('ok'),
        );
        $this->fail('Expected a 404 response.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(404);
    }
});
