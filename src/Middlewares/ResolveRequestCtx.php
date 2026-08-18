<?php

namespace TAFER\Core\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Context\RequestCtxRelation;
use TAFER\Core\Services\StoryblokVariableResolver;
use TAFER\Core\Support\RequestCtxSupport;

class ResolveRequestCtx
{
    public function __construct(
        private readonly RequestCtx $requestCtx,
        private readonly RequestCtxRelation $ctxRelation,
        private readonly StoryblokVariableResolver $variableResolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $segments = $request->segments();
        $locale = RequestCtxSupport::getLocaleBySegments($segments);

        $this->requestCtx
            ->setLocale($locale['locale'])
            ->setLocation(RequestCtxSupport::getLocationBySegments($segments))
            ->setSlug(RequestCtxSupport::getSlugWithoutLocaleBySegments($segments))
            ->setIsPreview($request->has('_storyblok'))
            ->setDevice(RequestCtxSupport::getDeviceByRequest($request));

        app()->setLocale($this->requestCtx->locale->value);
        view()->share('requestCtx', $this->requestCtx);
        view()->share('ctxRelation', $this->ctxRelation);
        view()->share('variableResolver', $this->variableResolver);

        return $next($request);
    }
}
