<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TAFER\Core\Dto\ReviewDto;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Services\ReviewsService;

/**
 * @param  array<int, mixed>  $queue
 * @param  array<int, array{request: RequestInterface, response?: ResponseInterface, error?: mixed}>  $history
 */
function makeReviewsServiceWithMock(array $queue, array &$history = []): ReviewsService
{
    $mock = new MockHandler($queue);

    $handlerStack = HandlerStack::create($mock);
    $handlerStack->push(Middleware::history($history));

    $client = new Client([
        'handler' => $handlerStack,
        'base_uri' => 'https://reviews.test/',
    ]);

    return new ReviewsService($client);
}

function fakeReviewPayload(array $overrides = []): array
{
    return array_merge([
        'id' => 3,
        'external_id' => '12346',
        'title' => 'Lorem ipsum',
        'excerpt' => 'Lorem ipsum dolor sit amet.',
        'rating' => 5,
        'published_at' => '2025-12-01T12:00:00Z',
        'user_name' => 'John Doe',
        'avatar_url' => 'https://placehold.co/400x400?text=John+Doe',
        'brand' => 'GB',
        'code' => 'GBPV',
        'language' => 'en',
        'destination' => 'PV',
        'created_at' => '2025-12-01T12:00:00Z',
        'updated_at' => '2025-12-01T12:00:00Z',
        'provider' => 'tripadvisor',
        'visibility' => 1,
    ], $overrides);
}

it('returns only visible five star reviews by hotel', function () {
    $history = [];

    $payload = [
        'success' => true,
        'data' => [
            fakeReviewPayload([
                'id' => 1,
                'rating' => 5,
                'visibility' => 0,
            ]),
            fakeReviewPayload([
                'id' => 2,
                'rating' => 3,
                'visibility' => 1,
            ]),
            fakeReviewPayload([
                'id' => 3,
                'rating' => 5,
                'visibility' => 1,
                'brand' => 'GB',
                'code' => 'GBPV',
            ]),
        ],
    ];

    $service = makeReviewsServiceWithMock([
        new Response(200, [], json_encode($payload)),
    ], $history);

    $hotelRegion = Resort::GarzaBlanca->region(Location::PuertoVallarta);

    $reviews = $service->getByHotel($hotelRegion, Locale::English);

    expect($reviews)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1);

    $review = $reviews->first();

    expect($review)->toBeInstanceOf(ReviewDto::class);

    expect($review->id)->toBe(3);
    expect($review->rating)->toBe(5);
    expect($review->visibility)->toBe(1);
    expect($review->brand)->toBe(Resort::GarzaBlanca);
    expect($review->code)->toBe('GBPV');

    $request = $history[0]['request'];

    expect($request->getMethod())->toBe('GET');
    expect($request->getUri()->getPath())->toBe('/code/GBPV');
    expect($request->getUri()->getQuery())->toBe('language=en');
    expect($request->getHeaderLine('Accept-Language'))->toBe('en');
});

it('returns only visible five star reviews by brand', function () {
    $history = [];

    $payload = [
        'success' => true,
        'data' => [
            fakeReviewPayload([
                'id' => 1,
                'brand' => 'GB',
                'code' => 'GBPV',
                'rating' => 5,
                'visibility' => 0,
            ]),
            fakeReviewPayload([
                'id' => 2,
                'brand' => 'GB',
                'code' => 'GBPV',
                'rating' => 3,
                'visibility' => 1,
            ]),
            fakeReviewPayload([
                'id' => 3,
                'brand' => 'GB',
                'code' => 'GBPV',
                'rating' => 5,
                'visibility' => 1,
            ]),
        ],
    ];

    $service = makeReviewsServiceWithMock([
        new Response(200, [], json_encode($payload)),
    ], $history);

    $reviews = $service->getByBrand(Resort::GarzaBlanca, Locale::English);

    expect($reviews)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1);

    $review = $reviews->first();

    expect($review)->toBeInstanceOf(ReviewDto::class);

    expect($review->id)->toBe(3);
    expect($review->rating)->toBe(5);
    expect($review->visibility)->toBe(1);
    expect($review->brand)->toBe(Resort::GarzaBlanca);
    expect($review->code)->toBe('GBPV');

    $request = $history[0]['request'];

    expect($request->getMethod())->toBe('GET');
    expect($request->getUri()->getPath())->toBe('/brand/'.Resort::GarzaBlanca->code());
    expect($request->getUri()->getQuery())->toBe('language=en');
    expect($request->getHeaderLine('Accept-Language'))->toBe('en');
});

it('returns an empty collection when no reviews match the filter', function () {
    $payload = [
        'success' => true,
        'data' => [
            fakeReviewPayload([
                'id' => 1,
                'rating' => 5,
                'visibility' => 0,
            ]),
            fakeReviewPayload([
                'id' => 2,
                'rating' => 4,
                'visibility' => 1,
            ]),
            fakeReviewPayload([
                'id' => 3,
                'rating' => 1,
                'visibility' => 0,
            ]),
        ],
    ];

    $service = makeReviewsServiceWithMock([
        new Response(200, [], json_encode($payload)),
    ]);

    $hotelRegion = Resort::GarzaBlanca->region(Location::PuertoVallarta);

    $reviews = $service->getByHotel($hotelRegion, Locale::English);

    expect($reviews)
        ->toBeInstanceOf(Collection::class)
        ->toBeEmpty();
});

it('returns an empty collection when the reviews api returns a client error', function () {
    $request = new Request('GET', 'code/GBPV');

    $service = makeReviewsServiceWithMock([
        new ClientException(
            'Bad request',
            $request,
            new Response(400, [], 'Invalid request')
        ),
    ]);

    $hotelRegion = Resort::GarzaBlanca->region(Location::PuertoVallarta);

    $reviews = $service->getByHotel($hotelRegion, Locale::English);

    expect($reviews)
        ->toBeInstanceOf(Collection::class)
        ->toBeEmpty();
});

it('returns an empty collection when the reviews api request fails', function () {
    $request = new Request('GET', 'code/GBPV');

    $service = makeReviewsServiceWithMock([
        new RequestException(
            'Connection error',
            $request
        ),
    ]);

    $hotelRegion = Resort::GarzaBlanca->region(Location::PuertoVallarta);

    $reviews = $service->getByHotel($hotelRegion, Locale::English);

    expect($reviews)
        ->toBeInstanceOf(Collection::class)
        ->toBeEmpty();
});

it('Must return an empty collection when data is unset', function () {
    $payload = [
        'success' => true,
    ];

    $service = makeReviewsServiceWithMock([
        new Response(200, [], json_encode($payload)),
    ]);

    $hotelRegion = Resort::GarzaBlanca->region(Location::PuertoVallarta);

    $reviews = $service->getByHotel($hotelRegion, Locale::English);

    expect($reviews)
        ->toBeInstanceOf(Collection::class)
        ->toBeEmpty();
});
