<?php

namespace TAFER\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GeoController
{
    private const UNKNOWN_COUNTRIES = ['XX'];

    public function __invoke(Request $request): JsonResponse
    {
        $header = config('tafer.geo.header');
        $country = $this->overrideCountry($request)
            ?? $this->normalize($request->header($header));

        return response()
            ->json(['country' => $country], 200)
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->setVary($header, false);
    }

    private function overrideCountry(Request $request): ?string
    {
        if (! config('tafer.geo.allow_override')) {
            return null;
        }

        return $this->normalize(
            $request->query(config('tafer.geo.override_query_param')),
        );
    }

    private function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^[A-Za-z]{2}$/', $value) !== 1) {
            return null;
        }

        $country = strtoupper($value);

        if (in_array($country, self::UNKNOWN_COUNTRIES, true)) {
            return null;
        }

        return $country;
    }
}
