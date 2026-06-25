<?php

namespace TAFER\Core\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;

class RequestCtxSupport
{
    /**
     * @param  string[]  $segments
     * @return array{locale: Locale, explicit: bool}
     */
    public static function getLocaleBySegments(array $segments): array
    {
        $firstSegment = Str::lower($segments[0] ?? '');
        $locale = Locale::tryFrom($firstSegment);

        return [
            'locale' => $locale ?? Locale::English,
            'explicit' => $locale !== null,
        ];
    }

    /**
     * @param  string[]  $segments
     */
    public static function getLocationBySegments(array $segments): Location
    {
        foreach ($segments as $segment) {
            $location = Location::tryFrom(Str::lower($segment));

            if ($location !== null) {
                return $location;
            }
        }

        return Location::Corp;
    }

    /**
     * @param  string[]  $segments
     */
    public static function getSlugWithoutLocaleBySegments(array $segments): string
    {
        if (isset($segments[0]) && Locale::tryFrom(Str::lower($segments[0])) !== null) {
            array_shift($segments);
        }

        return implode('/', $segments) ?: '/';
    }

    public static function getDeviceByRequest(Request $request): Device
    {
        $agent = new Agent;
        $agent->setHttpHeaders($request->server->all());
        $agent->setUserAgent($request->userAgent());

        return match (true) {
            $agent->isTablet() => Device::Tablet,
            $agent->isMobile() => Device::Mobile,
            default => Device::Desktop,
        };
    }
}
