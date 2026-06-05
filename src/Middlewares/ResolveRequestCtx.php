<?php 

namespace TAFER\Core\Middlewares;

use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Support\RequestCtxSupport;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class ResolveRequestCtx
{
    public function __construct(
        private readonly RequestCtx $requestCtx
    ){}

    public function handle(Request $request, Closure $next): Response
    {
        $requestSegments = $request->segments();

        $isPreview = $request->has('_storyblok');
        $locale = RequestCtxSupport::getLocaleBySegments($requestSegments);
        $location = RequestCtxSupport::getLocationBySegments($requestSegments);
        $slugWithoutLocale = RequestCtxSupport::getSlugWithoutLocaleBySegments($requestSegments);

        $this->requestCtx
            ->setLocale($locale['locale'])
            ->setLocation($location)
            ->setSlug($slugWithoutLocale)
            ->setIsPreview($isPreview);

        app()->setLocale($this->requestCtx->locale->value);
        view()->share('requestCtx', $this->requestCtx); 

        return $next($request);
    }

}
