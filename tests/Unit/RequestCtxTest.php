<?php

use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;

it('creates a request context from a valid brand slug', function () {
    $requestCtx = new RequestCtx('garza-blanca');

    expect($requestCtx->resort)->toBe(Resort::GarzaBlanca);
});

it('rejects invalid brand slugs', function () {
    new RequestCtx('unknown-resort');
})->throws(InvalidArgumentException::class, 'Invalid resort slug: unknown-resort');

it('sets request context values fluently', function () {
    $requestCtx = new RequestCtx('garza-blanca')
        ->setLocale(Locale::Spanish)
        ->setLocation(Location::Cancun)
        ->setSlug('special-offers-and-packages')
        ->setIsPreview(true)
        ->setDevice(Device::Mobile);

    expect($requestCtx->resort)->toBe(Resort::GarzaBlanca)
        ->and($requestCtx->locale)->toBe(Locale::Spanish)
        ->and($requestCtx->location)->toBe(Location::Cancun)
        ->and($requestCtx->slug)->toBe('special-offers-and-packages')
        ->and($requestCtx->isPreview)->toBeTrue()
        ->and($requestCtx->device)->toBe(Device::Mobile)
        ->and($requestCtx->device->isMobile())->toBeTrue();
});

it('does not allow a request context property to be set twice', function () {
    $requestCtx = (new RequestCtx('garza-blanca'))
        ->setLocale(Locale::English)
        ->setLocation(Location::Corp)
        ->setSlug('/')
        ->setIsPreview(false);

    $requestCtx->setSlug('special-offers');
})->throws(LogicException::class, 'RequestCtx property [slug] has already been set.');

it('does not allow preview mode to be set twice', function () {
    $requestCtx = (new RequestCtx('garza-blanca'))->setIsPreview(false);

    $requestCtx->setIsPreview(true);
})->throws(LogicException::class, 'RequestCtx property [isPreview] has already been set.');

it('does not allow device to be set twice', function () {
    $requestCtx = (new RequestCtx('garza-blanca'))->setDevice(Device::Desktop);

    $requestCtx->setDevice(Device::Mobile);
})->throws(LogicException::class, 'RequestCtx property [device] has already been set.');
