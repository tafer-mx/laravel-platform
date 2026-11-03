<?php

namespace TAFER\Core;

use GuzzleHttp\Client;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieQueue;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Api\Domain\Value\Resolver\LinkType;
use Storyblok\Api\StoriesApi;
use Storyblok\Api\StoriesApiInterface;
use Storyblok\Api\StoryblokClient;
use Storyblok\Api\StoryblokClientInterface;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Context\RequestCtxRelation;
use TAFER\Core\Contracts\PhoneResolver;
use TAFER\Core\Contracts\ReviewClient;
use TAFER\Core\Contracts\StoryblokCache;
use TAFER\Core\Contracts\StoryblokCacheInvalidator;
use TAFER\Core\Contracts\StoryblokGateway;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Phones\ConfigPhoneResolver;
use TAFER\Core\Phones\PartnerTracker;
use TAFER\Core\Services\CachedStoryblokService;
use TAFER\Core\Services\ReviewsService;
use TAFER\Core\Services\StoryblokContextResolver;
use TAFER\Core\Services\StoryblokService;
use TAFER\Core\Services\StoryblokVariableResolver;
use TAFER\Core\Services\SuitesEntityService;
use TAFER\Core\Storyblok\LaravelStoryblokCache;
use TAFER\Core\Storyblok\StoryblokCacheKey;
use TAFER\Core\Storyblok\StoryblokCachePolicy;
use TAFER\Core\Storyblok\StoryblokRequestFactory;
use TAFER\Core\Storyblok\StoryblokWebhookInvalidator;
use TAFER\Core\View\Components\StoryblokResolver;

class TAFERServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $applicationRelations = config('tafer.storyblok.resolve_relations');
        $this->replaceConfigRecursivelyFrom(__DIR__.'/../config/tafer.php', 'tafer');

        if (is_array($applicationRelations)) {
            config(['tafer.storyblok.resolve_relations' => $applicationRelations]);
        }

        $this->app->scoped(RequestCtx::class, fn () => new RequestCtx(
            $this->requiredConfig('tafer.brand.slug'),
        ));

        $this->app->singleton(ReviewClient::class, fn () => new ReviewsService(
            new Client([
                'base_uri' => $this->requiredConfig('tafer.middleware.base_url'),
                'timeout' => config('tafer.middleware.reviews.timeout'),
            ])
        ));

        $this->app->singleton(StoryblokClientInterface::class, fn () => new StoryblokClient(
            config('tafer.storyblok.base_uri', 'https://api.storyblok.com'),
            $this->requiredConfig('tafer.storyblok.token'),
            config('tafer.storyblok.timeout', 4),
        ));

        $this->app->singleton(StoriesApiInterface::class, fn () => new StoriesApi(
            $this->app->make(StoryblokClientInterface::class),
            config('tafer.storyblok.version', 'published'),
        ));

        $this->app->singleton(StoryblokService::class, fn () => new StoryblokService(
            $this->app->make(StoriesApiInterface::class),
        ));

        $this->app->singleton(StoryblokRequestFactory::class, function () {
            $locale = Locale::tryFrom(
                config('tafer.storyblok.default_locale', Locale::English->value),
            ) ?? Locale::English;
            $linkType = LinkType::tryFrom(
                config('tafer.storyblok.resolve_links', LinkType::Url->value),
            );

            return new StoryblokRequestFactory(
                relations: config('tafer.storyblok.resolve_relations', []),
                defaultLocale: $locale,
                resolveLinks: $linkType,
            );
        });

        $this->app->singleton(StoryblokCache::class, function () {
            $store = config('tafer.storyblok.cache.store');
            $storyTtl = (int) config('tafer.storyblok.cache.story_ttl', 0);
            $relationTtl = (int) config('tafer.storyblok.cache.relation_ttl', 0);

            return new LaravelStoryblokCache(
                cache: $this->app->make('cache')->store($store ?: null),
                keys: new StoryblokCacheKey(
                    config('tafer.storyblok.cache.prefix', 'tafer:storyblok'),
                ),
                storyTtl: $storyTtl > 0 ? $storyTtl : null,
                relationTtl: $relationTtl > 0 ? $relationTtl : null,
            );
        });

        $this->app->singleton(
            StoryblokCacheInvalidator::class,
            fn () => $this->app->make(StoryblokCache::class),
        );

        $this->app->singleton(StoryblokGateway::class, function () {
            $origin = $this->app->make(StoryblokService::class);

            if (! config('tafer.storyblok.cache.enabled', false)) {
                return $origin;
            }

            $version = Version::tryFrom(
                config('tafer.storyblok.version', Version::Published->value),
            ) ?? Version::Published;
            $locale = Locale::tryFrom(
                config('tafer.storyblok.default_locale', Locale::English->value),
            ) ?? Locale::English;

            return new CachedStoryblokService(
                origin: $origin,
                cache: $this->app->make(StoryblokCache::class),
                requests: $this->app->make(StoryblokRequestFactory::class),
                policy: new StoryblokCachePolicy,
                defaultVersion: $version,
                defaultLocale: $locale,
                cacheNamespace: config('tafer.storyblok.cache.namespace', 'default'),
            );
        });

        $this->app->singleton(StoryblokWebhookInvalidator::class, fn () => new StoryblokWebhookInvalidator(
            cache: $this->app->make(StoryblokCacheInvalidator::class),
            cacheNamespace: config('tafer.storyblok.cache.namespace', 'default'),
        ));

        // ========================================
        // Context Relations System
        // ========================================

        // Stack de contextos de Storyblok (scoped = se resetea por request)
        $this->app->scoped(RequestCtxRelation::class);

        // Resolver de contextos que usa el stack
        $this->app->scoped(
            StoryblokContextResolver::class,
            fn ($app) => new StoryblokContextResolver(
                $app->make(StoryblokGateway::class),
                $app->make(RequestCtxRelation::class),
            )
        );

        // Resolver de variables (singleton = stateless, no necesita resetearse)
        $this->app->singleton(StoryblokVariableResolver::class);

        $this->app->scoped(SuitesEntityService::class);

        // Teléfonos de reservaciones: el mapeo vive en el config de cada sitio (tafer.phones.config_key)
        $this->app->scoped(PartnerTracker::class, fn ($app) => new PartnerTracker(
            cookies: $app->make(CookieQueue::class),
            queryParam: config('tafer.phones.partner.query_param', 'partner'),
            cookieName: config('tafer.phones.partner.cookie', 'partner_id'),
            cookieMinutes: (int) config('tafer.phones.partner.cookie_minutes', 43200),
        ));

        $this->app->scoped(PhoneResolver::class, function ($app) {
            $defaultLocale = $this->phoneDefaultLocale();

            return new ConfigPhoneResolver(
                phones: (array) config(config('tafer.phones.config_key', 'tollfree'), []),
                partners: $app->make(PartnerTracker::class),
                defaultLocale: $defaultLocale,
                partnerLocales: $this->phonePartnerLocales($defaultLocale),
                locationScoped: (bool) config('tafer.phones.location_scoped', false),
                timezone: $this->phoneTimezone(),
                timeOverrideParam: $this->phoneTimeOverrideAllowed()
                    ? config('tafer.phones.schedules.time_override_param', 'phone_time')
                    : null,
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/tafer.php' => config_path('tafer.php'),
        ], 'tafer-config');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tafer');

        // Class Components
        // https://laravel.com/docs/13.x/packages#view-components
        Blade::component('storyblok._resolver', StoryblokResolver::class);

    }

    private function phoneDefaultLocale(): Locale
    {
        return Locale::tryFrom((string) (
            config('tafer.phones.default_locale')
                ?? config('tafer.storyblok.default_locale', Locale::English->value)
        )) ?? Locale::English;
    }

    /**
     * Indica si ?phone_time=HH:MM puede forzar la hora de los horarios. Por defecto solo en el entorno local.
     */
    private function phoneTimeOverrideAllowed(): bool
    {
        $allowed = config('tafer.phones.schedules.allow_time_override');

        return $allowed === null ? $this->app->isLocal() : (bool) $allowed;
    }

    /**
     * Zona horaria de los horarios de teléfonos. Cada sitio puede definir resort_timezone(), como espera OfferValidityHelper.
     */
    private function phoneTimezone(): string
    {
        $timezone = config('tafer.phones.schedules.timezone')
            ?? (function_exists('resort_timezone') ? resort_timezone() : null)
            ?? config('app.timezone');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'UTC';
    }

    /**
     * @return list<Locale>
     */
    private function phonePartnerLocales(Locale $defaultLocale): array
    {
        $locales = config('tafer.phones.partner_locales');

        if (! is_array($locales)) {
            return [$defaultLocale];
        }

        return array_values(array_filter(array_map(
            fn (mixed $locale): ?Locale => $locale instanceof Locale ? $locale : Locale::tryFrom((string) $locale),
            $locales,
        )));
    }

    private function requiredConfig(string $key): string
    {
        $value = config($key);

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("Missing required config value [{$key}].");
        }

        return trim($value);
    }
}
