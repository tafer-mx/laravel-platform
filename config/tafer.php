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

    'geo' => [
        'header' => 'CF-IPCountry',
        'allow_override' => env('GEO_ALLOW_OVERRIDE', env('APP_DEBUG', false)),
        'override_query_param' => 'country',
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

    /*
    | Suite entities (component: suites-data) under brands/{brand}/{entities_path}/.
    | Used by SuitesEntityService and shared suite listing filters.
    */
    'suites' => [
        'entities_path' => 'entities/suites',
    ],

    /*
    | Teléfonos de reservaciones (TAFER\Core\Contracts\PhoneResolver).
    | El mapa de números vive en cada sitio, en `config_key` (por defecto config/tollfree.php).
    |
    | - default_locale: idioma principal del sitio, usado como fallback. null = tafer.storyblok.default_locale.
    | - partner_locales: idiomas en los que aplican los números de partner. null = [default_locale].
    | - location_scoped: true si el mapa está agrupado por location ('cancun' => [...]).
    */
    'phones' => [
        'config_key' => 'tollfree',
        'default_locale' => null,
        'partner_locales' => null,
        'location_scoped' => false,
        'partner' => [
            'query_param' => 'partner',
            'cookie' => 'partner_id',
            'cookie_minutes' => 43200, // 30 días
        ],
        /*
        | Números por horario (`schedules` en el mapa de números, ej: Bookline fuera de horario).
        | - timezone: para horarios sin zona horaria propia. null = resort_timezone() o app.timezone.
        | - allow_time_override: permite forzar la hora para QA con ?{time_override_param}=23:30.
        |   null = solo en el entorno local (APP_ENV=local). Por defecto nunca se activa en producción.
        */
        'schedules' => [
            'timezone' => null,
            'allow_time_override' => null,
            'time_override_param' => 'phone_time',
        ],
    ],

    /*
    | PDF download security configuration
    | lista de host, timeouts y tamaño maximo de descarga
    */
    'download' => [
        'allowed_hosts' => [
            'a.storyblok.com',
        ],
        'allowed_host_suffixes' => [
            '.storyblok.com',
        ],
        'max_size_bytes' => 25 * 1024 * 1024, // 25 MB
        'timeout_seconds' => 10,
        'connect_timeout_seconds' => 3,

    ],
];
