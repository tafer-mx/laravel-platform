<?php

use TAFER\Core\DTO\ReviewDTO;
use TAFER\Core\Enums\Locale;
use Tests\Support;
use Tests\Support\Fixture;


it('Must return a ReviewDto', function () {
    $json = Fixture::getTestFixture('review-response.fixture.json');
    $rawReview = $json['en']['data'][0];

    $dto = ReviewDTO::fromArray($rawReview);
    expect($dto)->toBeInstanceOf(ReviewDTO::class);
});

it('Must return an array', function () {
    $json = Fixture::getTestFixture('review-response.fixture.json');
    $rawReview = $json['en']['data'][0];

    $dto = ReviewDTO::fromArray($rawReview);

    expect($dto->toArray())->toBeArray();
});

it('Must return the expected array structure', function () {
    $json = Fixture::getTestFixture('review-response.fixture.json');
    $rawReview = $json['en']['data'][0];

    $dto = ReviewDTO::fromArray($rawReview);
    $array = $dto->toArray();

    expect($array)
        ->toHaveKeys([
            'external_id',
            'title',
            'excerpt',
            'rating',
            'published_at',
            'user_name',
            'avatar_url',
            'brand',
            'destination',
            'code',
            'language',
            'provider',
            'visibility',
            'created_at',
            'updated_at'
        ]);
});

it('Must be json encodable', function () {
    $json = Fixture::getTestFixture('review-response.fixture.json');
    $rawReview = $json['en']['data'][0];

    $dto = ReviewDTO::fromArray($rawReview);

    $encoded = json_encode($dto->toArray(), JSON_THROW_ON_ERROR);

    expect($encoded)->toBeString()
        ->and(json_decode($encoded, true, 512, JSON_THROW_ON_ERROR))->toBeArray();
});


it('Must not expose enum objects in array output', function () {
    $json = Fixture::getTestFixture('review-response.fixture.json');
    $rawReview = $json['en']['data'][0];

    $dto = ReviewDTO::fromArray($rawReview);
    $arrDto = $dto->toArray();

    expect($arrDto['language'])->toBe($rawReview['language'])
        ->and($arrDto['brand'])->toBe($rawReview['brand'])
        ->and($arrDto['destination'])->toBe($rawReview['destination']);
});