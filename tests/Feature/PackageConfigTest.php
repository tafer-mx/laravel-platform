<?php

use GuzzleHttp\Client;
use Orchestra\Testbench\TestCase;
use Storyblok\Api\StoryblokClientInterface;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Contracts\ReviewClient;
use TAFER\Core\Services\ReviewsService;

uses(TestCase::class);

it('requires a brand slug to resolve the request context', function () {
    config(['tafer.brand.slug' => '']);

    app(RequestCtx::class);
})->throws(InvalidArgumentException::class, 'Missing required config value [tafer.brand.slug].');

it('requires a Storyblok token to resolve the Storyblok client', function () {
    config(['tafer.storyblok.token' => '']);

    app(StoryblokClientInterface::class);
})->throws(InvalidArgumentException::class, 'Missing required config value [tafer.storyblok.token].');

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
