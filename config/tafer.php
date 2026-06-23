<?php

return [
    'middleware' => [
        'base_url' => env('TAFER_MIDDLEWARE_BASE_URL'),
        'reviews' => [
            'timeout' => (int) env('TAFER_MIDDLEWARE_REVIEWS_TIMEOUT', 4),
        ],
    ],

    'storyblok' => [
        'base_uri' => env('STORYBLOK_BASE_URI', 'https://api.storyblok.com'),
        'token' => env('STORYBLOK_TOKEN', ''),
        'timeout' => (int) env('STORYBLOK_TIMEOUT', 4),
        'version' => env('STORYBLOK_VERSION', 'published'),
        'default_locale' => env('STORYBLOK_DEFAULT_LOCALE', 'en'),
        'resolve_links' => env('STORYBLOK_RESOLVE_LINKS', 'url'),
        'resolve_relations' => [
            'Basic_reference.reference',
            'Basic_Reference_Articles.reference',
            'Element_references.Content_info_pages',
            'reference_dining_global.dining',
            'reference_residence_global.residence',
            'reference_showcase_global.reference_showcase',
            'reference_suite-v1_global.suite',
            'Blog_Page.Category',
            'offer_page.offer_relation',
            'section-offer-carousel.offers',
            'section-dining-carousel.restaurants',
        ],

        'cache' => [
            'enabled' => (bool) env('STORYBLOK_CACHE_ENABLED', false),
            'store' => env('STORYBLOK_CACHE_STORE'),
            'story_ttl' => (int) env('STORYBLOK_CACHE_STORY_TTL', 0),
            'relation_ttl' => (int) env('STORYBLOK_CACHE_RELATION_TTL', 0),
            'prefix' => env('STORYBLOK_CACHE_PREFIX', 'tafer:storyblok'),
            'namespace' => env('STORYBLOK_CACHE_NAMESPACE', 'default'),
        ],
    ],
];
