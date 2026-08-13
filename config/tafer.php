<?php

return [
    'brand' => [
        'slug' => 'mousai',
    ],

    'middleware' => [
        'base_url' => 'https://middleware.taferresorts.com/',
        'reviews' => [
            'timeout' => 4,
        ],
    ],

    'storyblok' => [
        'base_uri' => 'https://api.storyblok.com',
        'token' => '',
        'timeout' => 4,
        'version' => 'published',
        'default_locale' => 'en',
        'resolve_links' => 'url',
        'global_config' => [
            'location_scoped' => true,
        ],
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
            'enabled' => true,
            'store' => 'database',
            'story_ttl' => 0,
            'relation_ttl' => 0,
            'prefix' => 'tafer:storyblok',
            'namespace' => 'mousai',
        ],
    ],
];
