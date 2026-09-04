<?php

use TAFER\Core\Support\SuiteEntityHelper;

it('normalizes suites-data component with beds and view fields', function () {
    $story = [
        'uuid' => 'suite-uuid-1',
        'name' => 'Deluxe Ocean View',
        'content' => [
            'component' => 'suites-data',
            'title' => 'Deluxe Ocean View Suite',
            'image' => ['filename' => 'https://example.com/suite.jpg'],
            'beds' => 1,
            'view' => 'ocean-view',
            'amenities' => [
                [
                    'component' => 'basic-amenetie-icon',
                    'text' => 'WiFi',
                    'image_icon' => ['filename' => 'wifi.svg'],
                ],
            ],
            'suite_link' => ['url' => '/suites/deluxe-ocean-view'],
        ],
    ];

    $context = TAFER\Core\Context\StoryblokBlockContext::empty()->withResolvedStory($story);

    expect($context->get('component'))->toBe('suites-data')
        ->and($context->get('title'))->toBe('Deluxe Ocean View Suite')
        ->and($context->get('beds'))->toBe(1)
        ->and($context->get('view'))->toBe('ocean-view')
        ->and($context->get('link'))->toBe(['url' => '/suites/deluxe-ocean-view']);
});

it('maps a suites-data story into a normalized suite payload', function () {
    $story = [
        'uuid' => 'suite-uuid-1',
        'name' => 'Fallback Name',
        'content' => [
            'component' => 'suites-data',
            'title' => 'One Bedroom Suite',
            'beds' => 2,
            'view' => 'garden-view',
            'amenities' => [
                [
                    'component' => 'basic-amenetie-icon',
                    'text' => 'Balcony',
                    'image_icon' => ['filename' => 'balcony.svg'],
                ],
                [
                    'component' => 'basic-amenetie-icon',
                    'text' => '',
                    'image_icon' => ['filename' => 'ignored.svg'],
                ],
            ],
            'suite_link' => ['url' => '/suites/one-bedroom'],
        ],
    ];

    $suite = SuiteEntityHelper::fromStory($story);

    expect($suite)->toMatchArray([
        'uuid' => 'suite-uuid-1',
        'title' => 'One Bedroom Suite',
        'beds' => 2,
        'view' => 'garden-view',
        'link' => ['url' => '/suites/one-bedroom'],
        'tags' => ['beds-2', 'garden-view'],
    ])->and($suite['amenities'])->toBe([
        ['label' => 'Balcony', 'icon' => 'balcony.svg'],
    ]);
});

it('builds filters dynamically from suite tags', function () {
    $suites = [
        ['tags' => ['beds-2', 'garden-view']],
        ['tags' => ['beds-1', 'ocean-view']],
    ];

    $filters = SuiteEntityHelper::buildFilters(
        suites: $suites,
        labelResolver: fn (string $tag): string => strtoupper($tag),
    );

    expect($filters)->toBe([
        ['tag' => 'all', 'label' => 'ALL'],
        ['tag' => 'beds-1', 'label' => 'BEDS-1'],
        ['tag' => 'beds-2', 'label' => 'BEDS-2'],
        ['tag' => 'garden-view', 'label' => 'GARDEN-VIEW'],
        ['tag' => 'ocean-view', 'label' => 'OCEAN-VIEW'],
    ]);
});

it('respects cms filter options order when provided', function () {
    $suites = [
        ['tags' => ['beds-2', 'ocean-view']],
        ['tags' => ['beds-1', 'garden-view']],
    ];

    $filters = SuiteEntityHelper::buildFilters(
        suites: $suites,
        labelResolver: fn (string $tag): string => $tag,
        bedsOptions: [2, 1],
        viewOptions: ['ocean-view', 'garden-view'],
    );

    expect(array_column($filters, 'tag'))->toBe([
        'all',
        'beds-2',
        'beds-1',
        'ocean-view',
        'garden-view',
    ]);
});

it('omits cms options that no suite uses', function () {
    $suites = [
        ['tags' => ['beds-1', 'ocean-view']],
    ];

    $filters = SuiteEntityHelper::buildFilters(
        suites: $suites,
        labelResolver: fn (string $tag): string => $tag,
        bedsOptions: [1, 2, 3],
        viewOptions: ['ocean-view', 'garden-view', 'pool-view'],
    );

    expect(array_column($filters, 'tag'))->toBe([
        'all',
        'beds-1',
        'ocean-view',
    ]);
});

it('selects stories by uuid preserving order', function () {
    $stories = [
        ['uuid' => 'a', 'name' => 'A'],
        ['uuid' => 'b', 'name' => 'B'],
        ['uuid' => 'c', 'name' => 'C'],
    ];

    $selected = SuiteEntityHelper::selectByUuids($stories, ['c', 'a', 'missing']);

    expect($selected)->toBe([
        ['uuid' => 'c', 'name' => 'C'],
        ['uuid' => 'a', 'name' => 'A'],
    ]);
});
