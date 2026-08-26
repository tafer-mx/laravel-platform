<?php

use Illuminate\Http\Request;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Support\RequestCtxSupport;

const URLS = [
    'https://garzablancaresort.com/puerto-vallarta/special-offers-and-packages/loyalty-sale',
    'https://garzablancaresort.com/es/puerto-vallarta/special-offers-and-packages/loyalty-sale',
    'https://garzablancaresort.com/en/puerto-vallarta/special-offers-and-packages/loyalty-sale',
    'https://villapalmarcancun.com/special-offers-and-packages/loyalty-sale',
    'https://villapalmarcancun.com/es/special-offers-and-packages/loyalty-sale',
    'https://villapalmarcancun.com/en/special-offers-and-packages/loyalty-sale',
    'https://villapalmarmancun.com/en',
    'https://villapalmarmancun.com/es',
    'https://villapalmarmancun.com/',
];

it('resolves locale from URL segments', function () {
    foreach (URLS as $url) {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $path = trim($path, '/');
        $segments = $path === '' ? [] : explode('/', $path);

        $result = RequestCtxSupport::getLocaleBySegments($segments);

        if (preg_match('#/en(/|$)#', $url)) {
            expect($result['locale']->value)->toBe('en');
            expect($result['explicit'])->toBeTrue();
        } elseif (preg_match('#/es(/|$)#', $url)) {
            expect($result['locale']->value)->toBe('es');
            expect($result['explicit'])->toBeTrue();
        } else {
            expect($result['locale']->value)->toBe('en');
            expect($result['explicit'])->toBeFalse();
        }
    }
});

it('resolves location from URL segments', function () {
    $urls = [
        'https://garzablancaresort.com/puerto-vallarta' => Location::PuertoVallarta,
        'https://garzablancaresort.com/cancun' => Location::Cancun,
        'https://garzablancaresort.com/los-cabos' => Location::Cabo,
        'https://villapalmarcancun.com/' => Location::Corp,
        'https://hotelmousai.com/' => Location::Corp,
        'https://hotelmousai.com/cancun' => Location::Cancun,
        'https://hotelmousai.com/puerto-vallarta' => Location::PuertoVallarta,
    ];

    foreach ($urls as $url => $expectedLocation) {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $path = trim($path, '/');
        $segments = $path === '' ? [] : explode('/', $path);

        $location = RequestCtxSupport::getLocationBySegments($segments);

        expect($location)->toBe($expectedLocation);
    }
});

it('returns slug without locale prefix from URL segments', function () {
    $urls = [
        'https://example.com/en/puerto-vallarta/special-offers' => 'puerto-vallarta/special-offers',
        'https://example.com/es/puerto-vallarta/special-offers' => 'puerto-vallarta/special-offers',
        'https://example.com/en' => '/',
        'https://example.com/es' => '/',
        'https://example.com/' => '/',
        'https://example.com/puerto-vallarta' => 'puerto-vallarta',
    ];

    foreach ($urls as $url => $expectedSlug) {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $path = trim($path, '/');
        $segments = $path === '' ? [] : explode('/', $path);

        $slug = RequestCtxSupport::getSlugWithoutLocaleBySegments($segments);

        expect($slug)->toBe($expectedSlug);
    }
});

it('resolves a child resort from the exact location and resort pattern', function (array $segments) {
    expect(RequestCtxSupport::getChildResortBySegments($segments, Resort::GarzaBlanca))
        ->toBe(Resort::Sanctuary);
})->with([
    'without locale' => [['puerto-vallarta', 'sanctuary', 'suites']],
    'Spanish locale' => [['es', 'puerto-vallarta', 'sanctuary', 'suites']],
    'English locale' => [['en', 'puerto-vallarta', 'sanctuary', 'suites']],
]);

it('does not resolve a child resort outside the exact parent and region pattern', function (
    array $segments,
    Resort $parent,
) {
    expect(RequestCtxSupport::getChildResortBySegments($segments, $parent))->toBeNull();
})->with([
    'ordinary parent route' => [['puerto-vallarta', 'offers'], Resort::GarzaBlanca],
    'missing location' => [['sanctuary', 'suites'], Resort::GarzaBlanca],
    'child in a later segment' => [['puerto-vallarta', 'foo', 'sanctuary'], Resort::GarzaBlanca],
    'unsupported region' => [['cancun', 'sanctuary'], Resort::GarzaBlanca],
    'different parent resort' => [['puerto-vallarta', 'sanctuary'], Resort::HotelMousai],
]);

it('resolves device from the request user agent headers', function () {
    $mobileRequest = Request::create(
        '/special-offers',
        'GET',
        [],
        [],
        [],
        [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ]
    );

    $desktopRequest = Request::create(
        '/special-offers',
        'GET',
        [],
        [],
        [],
        [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]
    );

    $tabletRequest = Request::create(
        '/special-offers',
        'GET',
        [],
        [],
        [],
        [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ]
    );

    expect(RequestCtxSupport::getDeviceByRequest($mobileRequest))->toBe(Device::Mobile)
        ->and(RequestCtxSupport::getDeviceByRequest($tabletRequest))->toBe(Device::Tablet)
        ->and(RequestCtxSupport::getDeviceByRequest($desktopRequest))->toBe(Device::Desktop);
});
