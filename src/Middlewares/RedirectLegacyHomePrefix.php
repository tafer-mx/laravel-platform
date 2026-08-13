<?php

namespace TAFER\Core\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Support\RequestCtxSupport;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class RedirectLegacyHomePrefix
{
    public function __construct(
        private RequestCtx $ctx,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestPath = trim($request->path(), '/');

        if (Str::startsWith($requestPath, 'brands') || Str::startsWith($requestPath, 'es/brands')) {
            return abort(410);
        }

        if (! $this->hasResolvedContext()) {
            return $next($request);
        }

        $localeInfo = RequestCtxSupport::getLocaleBySegments($request->segments());
        $cleanPath = $this->cleanPath();

        if ($localeInfo['explicit'] && $localeInfo['locale'] === Locale::English) {
            return redirect($cleanPath);
        }

        if (! $this->usesLocationPrefixes() && ! $this->ctx->location->isCorp()) {
            abort(404);
        }

        if ($this->isLegacyHomePrefix()) {
            abort(404);
        }

        if ($this->ctx->location->isCorp()) {
            $prefix = $this->ctx->locale === Locale::Spanish ? "es{$cleanPath}" : $cleanPath;
            $prefix = trim($prefix, '/');

            if ($prefix !== '' && Str::doesntStartWith($requestPath, $prefix)) {
                abort(404);
            }
        } else {
            $prefix = $this->ctx->locale === Locale::Spanish ? "es/{$this->ctx->location->value}" : $this->ctx->location->value;
            if (Str::doesntStartWith($requestPath, $prefix)) {
                abort(404);
            }
        }

        return $next($request);
    }

    private function cleanPath(): string
    {
        return $this->ctx->slug === '/' ? '/' : '/'.$this->ctx->slug;
    }

    private function isLegacyHomePrefix(): bool
    {
        $homeSlug = ! $this->usesLocationPrefixes() || $this->ctx->location->isCorp()
            ? "home-{$this->ctx->resort->value}"
            : "home-{$this->ctx->location->value}";
        $slug = $this->slugWithoutLocationPrefix();

        return $slug === $homeSlug
            || Str::startsWith($slug, "{$homeSlug}/");
    }

    private function slugWithoutLocationPrefix(): string
    {
        if ($this->ctx->slug === '/') {
            return '';
        }

        $slug = trim($this->ctx->slug, '/');

        if (! $this->usesLocationPrefixes() || $this->ctx->location->isCorp()) {
            return $slug;
        }

        $location = $this->ctx->location->value;

        if ($slug === $location) {
            return '';
        }

        if (Str::startsWith($slug, "{$location}/")) {
            return Str::after($slug, "{$location}/");
        }

        return $slug;
    }

    private function usesLocationPrefixes(): bool
    {
        return $this->ctx->resort !== Resort::VillaPalmarCancun;
    }

    private function hasResolvedContext(): bool
    {
        return isset(
            $this->ctx->locale,
            $this->ctx->location,
            $this->ctx->slug,
            $this->ctx->isPreview,
            $this->ctx->device,
        );
    }
}
