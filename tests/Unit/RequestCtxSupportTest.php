<?php 

use TAFER\Core\Support\RequestCtxSupport;
use TAFER\Core\Enums\Location;

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

