<?php

namespace TAFER\Core\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TAFER\Core\Context\RequestCtx;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class SetCacheHeaders
{
    public function __construct(protected RequestCtx $requestCtx) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $response->setVary('User-Agent', false);
        }

        if (
            $request->isMethod('GET') &&
            $response->isSuccessful() &&
            ! $request->expectsJson() &&
            ! $request->ajax() &&
            ! $request->is('download/*')
        ) {

            // Evitar cualquier tipo de caché si la petición proviene del editor de Storyblok
            if ($this->requestCtx->isPreview) {
                $response->headers->set(
                    'Cache-Control',
                    'no-cache, no-store, must-revalidate, max-age=0'
                );

                return $response;
            }
            $response->headers->remove('Pragma');
            $response->headers->remove('Expires');

            $response->headers->set(
                'Cache-Control',
                'public, max-age=300'
            );
        }

        return $response;
    }
}
