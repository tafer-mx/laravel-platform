<?php

use Eris\Generators;
use Eris\TestTrait;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase;
use TAFER\Core\Http\Controllers\GeoController;
use TAFER\Core\TAFERServiceProvider;

abstract class GeoControllerPropertyTestCase extends TestCase
{
    use TestTrait;
}

uses(GeoControllerPropertyTestCase::class);

beforeEach(function () {
    $this->app->register(TAFERServiceProvider::class);

    Route::get('/fixtures/geo', GeoController::class);

    config([
        'tafer.geo.header' => 'CF-IPCountry',
        'tafer.geo.allow_override' => false,
        'tafer.geo.override_query_param' => 'country',
    ]);
});

function geoFixtureResponse(TestCase $testCase, ?string $headerValue = null, string $uri = '/fixtures/geo')
{
    $request = $testCase->get($uri, $headerValue === null ? [] : [
        'CF-IPCountry' => $headerValue,
    ]);

    return $request;
}

function cacheControlDirectives(string $header): array
{
    $directives = [];

    foreach (explode(',', $header) as $directive) {
        [$name, $value] = array_pad(explode('=', trim($directive), 2), 2, true);
        $directives[strtolower($name)] = $value;
    }

    ksort($directives);

    return $directives;
}

it('normalizes casing and exterior spaces from the configured country header', function (string $header) {
    geoFixtureResponse($this, $header)
        ->assertOk()
        ->assertExactJson(['country' => 'MX']);
})->with([
    'lowercase' => 'mx',
    'uppercase' => 'MX',
    'mixed case' => 'mX',
    'exterior spaces' => '  mx  ',
    'exterior tabs' => "\tmx\t",
]);

it('returns null when the country header is absent', function () {
    geoFixtureResponse($this)
        ->assertOk()
        ->assertExactJson(['country' => null]);
});

it('returns null for invalid country header values', function (string $header) {
    geoFixtureResponse($this, $header)
        ->assertOk()
        ->assertExactJson(['country' => null]);
})->with([
    'empty' => '',
    'spaces only' => '   ',
    'unknown uppercase' => 'XX',
    'unknown lowercase' => 'xx',
    'unknown mixed case' => 'xX',
    'one character' => 'M',
    'three characters' => 'MEX',
    'digit suffix' => 'M1',
    'digits only' => '12',
    'hyphen' => 'M-',
    'symbols only' => '$#',
    'non ASCII letter' => 'Mé',
    'Unicode homoglyph' => 'ΜX',
]);

it('uses a valid enabled override before the configured header', function () {
    config(['tafer.geo.allow_override' => true]);

    geoFixtureResponse($this, 'mx', '/fixtures/geo?country=%20ca%20')
        ->assertOk()
        ->assertExactJson(['country' => 'CA']);
});

it('falls back to the header when an enabled override is absent or invalid', function (?string $override) {
    config(['tafer.geo.allow_override' => true]);

    $uri = '/fixtures/geo';

    if ($override !== null) {
        $uri .= '?'.http_build_query(['country' => $override]);
    }

    geoFixtureResponse($this, ' mx ', $uri)
        ->assertOk()
        ->assertExactJson(['country' => 'MX']);
})->with([
    'absent' => null,
    'empty' => '',
    'spaces only' => '   ',
    'one character' => 'C',
    'three characters' => 'CAN',
    'digit' => 'C1',
    'symbol' => 'C-',
    'unknown' => 'xX',
    'non ASCII' => 'CÁ',
]);

it('ignores the override query parameter when overrides are disabled', function () {
    config(['tafer.geo.allow_override' => false]);

    geoFixtureResponse($this, 'mx', '/fixtures/geo?country=ca')
        ->assertOk()
        ->assertExactJson(['country' => 'MX']);
});

it('uses only the configured header and configured override parameter', function () {
    config([
        'tafer.geo.header' => 'X-Visitor-Country',
        'tafer.geo.allow_override' => true,
        'tafer.geo.override_query_param' => 'qa_country',
    ]);

    $response = $this->get('/fixtures/geo?country=CA&qa_country=us', [
        'CF-IPCountry' => 'MX',
        'X-Visitor-Country' => 'BR',
    ]);

    $response
        ->assertOk()
        ->assertExactJson(['country' => 'US']);
});

it('returns the exact HTTP response contract for a normalized country', function () {
    $response = geoFixtureResponse($this, 'mx');

    $response
        ->assertStatus(200)
        ->assertExactJson(['country' => 'MX']);

    expect(array_keys($response->json()))->toBe(['country'])
        ->and(cacheControlDirectives($response->headers->get('Cache-Control')))->toBe([
            'max-age' => '0',
            'no-store' => true,
            'private' => true,
        ])
        ->and($response->headers->all('Vary'))->toBe(['CF-IPCountry']);
});

it('returns the exact single-key body when the country is unknown', function () {
    $response = geoFixtureResponse($this, 'XX');

    $response
        ->assertStatus(200)
        ->assertExactJson(['country' => null]);

    expect(array_keys($response->json()))->toBe(['country']);
});

it('varies the response by the configured country header', function () {
    config(['tafer.geo.header' => 'X-Visitor-Country']);

    $response = $this->get('/fixtures/geo', [
        'X-Visitor-Country' => 'mx',
    ]);

    $response
        ->assertOk()
        ->assertExactJson(['country' => 'MX']);

    expect($response->headers->all('Vary'))->toBe(['X-Visitor-Country']);
});

function validCountryGenerator()
{
    $letter = Generators::elements(array_merge(range('A', 'Z'), range('a', 'z')));
    $exteriorWhitespace = Generators::elements(['', ' ', '  ', "\t", " \t"]);

    return Generators::suchThat(
        fn (string $value): bool => strtoupper(trim($value)) !== 'XX',
        Generators::map(
            fn (array $parts): string => $parts[4].$parts[0].$parts[1].$parts[5],
            Generators::tuple(
                $letter,
                $letter,
                Generators::bool(),
                Generators::bool(),
                $exteriorWhitespace,
                $exteriorWhitespace,
            ),
        ),
    );
}

function invalidCountryStringGenerator()
{
    return Generators::elements([
        '', ' ', "\t", "\n", "\r", "\0", "\x0B", '   ',
        'X', 'M', 'USA', 'MEX', 'ABCD', '1', '12', 'M1', '1X',
        '-', 'M-', '_X', '$#', 'A B', 'X.X', 'XX', 'xx', 'xX', 'Xx',
        'Mé', 'CÁ', 'ñ', '日本', '😀', 'ΜX', 'ХX', 'ＡB', "\xC3\x28",
    ]);
}

function invalidCountryGenerator()
{
    return Generators::oneOf(
        invalidCountryStringGenerator(),
        Generators::elements([null, false, true, 0, 1, -1, 1.5, [], ['MX'], new stdClass]),
    );
}

function possibleHeaderGenerator()
{
    return Generators::oneOf(
        validCountryGenerator(),
        invalidCountryStringGenerator(),
        Generators::constant(null),
    );
}

function possibleOverrideGenerator()
{
    return Generators::oneOf(
        validCountryGenerator(),
        invalidCountryStringGenerator(),
        Generators::constant(null),
    );
}

function normalizeCountryThroughController(mixed $value): ?string
{
    static $method;

    $method ??= new ReflectionMethod(GeoController::class, 'normalize');
    $method->setAccessible(true);

    return $method->invoke(new GeoController, $value);
}

function geoPropertyResponse(
    TestCase $testCase,
    ?string $header,
    mixed $override,
    bool $includeOverride,
): TestResponse {
    $parameters = $includeOverride ? ['country' => $override] : [];
    $server = $header === null ? [] : ['HTTP_CF_IPCOUNTRY' => $header];

    return $testCase->call('GET', '/fixtures/geo', $parameters, [], [], $server);
}

// Feature: shared-visitor-geolocation, Property 1: Normalización canónica de códigos válidos
it('property 1 canonically normalizes every valid ASCII country code', function () {
    $this->limitTo(100)
        ->forAll(validCountryGenerator())
        ->then(function (string $country): void {
            expect(normalizeCountryThroughController($country))->toBe(strtoupper(trim($country)));
        });
});

// Feature: shared-visitor-geolocation, Property 2: Rechazo total de países inválidos
it('property 2 rejects every invalid or non textual country value', function () {
    $this->limitTo(100)
        ->forAll(invalidCountryGenerator())
        ->then(function (mixed $country): void {
            expect(normalizeCountryThroughController($country))->toBeNull();
        });
});

// Feature: shared-visitor-geolocation, Property 3: Precedencia del override válido
it('property 3 gives every valid enabled override precedence over an independent valid header', function () {
    config(['tafer.geo.allow_override' => true]);

    $this->limitTo(100)
        ->forAll(validCountryGenerator(), validCountryGenerator())
        ->then(function (string $override, string $header): void {
            geoPropertyResponse($this, $header, $override, true)
                ->assertOk()
                ->assertExactJson(['country' => strtoupper(trim($override))]);
        });
});

// Feature: shared-visitor-geolocation, Property 4: Fallback de cualquier override inválido
it('property 4 falls back from every invalid override to an independent header', function () {
    config(['tafer.geo.allow_override' => true]);

    $this->limitTo(100)
        ->forAll(invalidCountryStringGenerator(), possibleHeaderGenerator())
        ->then(function (string $override, ?string $header): void {
            geoPropertyResponse($this, $header, $override, true)
                ->assertOk()
                ->assertExactJson(['country' => normalizeCountryThroughController($header)]);
        });
});

// Feature: shared-visitor-geolocation, Property 5: Override deshabilitado no altera la resolución
it('property 5 ignores every override when overrides are disabled', function () {
    config(['tafer.geo.allow_override' => false]);

    $this->limitTo(100)
        ->forAll(possibleOverrideGenerator(), possibleHeaderGenerator())
        ->then(function (?string $override, ?string $header): void {
            geoPropertyResponse($this, $header, $override, true)
                ->assertOk()
                ->assertExactJson(['country' => normalizeCountryThroughController($header)]);
        });
});

function frozenLocalGeoCountry(?string $header, ?string $override, bool $allowOverride): ?string
{
    $normalize = static function (?string $value): ?string {
        if ($value === null) {
            return null;
        }

        $value = strtoupper(trim($value));

        if (preg_match('/^[A-Z]{2}$/', $value) !== 1 || $value === 'XX') {
            return null;
        }

        return $value;
    };

    return ($allowOverride ? $normalize($override) : null) ?? $normalize($header);
}

// Feature: shared-visitor-geolocation, Property 9: Equivalencia observable durante la migración
// Validates: Requirements 6.10
it('property 9 matches the frozen local controller model for every generated request', function () {
    $this->limitTo(100)
        ->forAll(possibleHeaderGenerator(), possibleOverrideGenerator(), Generators::bool())
        ->then(function (?string $header, ?string $override, bool $allowOverride): void {
            config(['tafer.geo.allow_override' => $allowOverride]);

            $response = geoPropertyResponse($this, $header, $override, true);
            $expectedCountry = frozenLocalGeoCountry($header, $override, $allowOverride);

            expect($response->status())->toBe(200)
                ->and($response->json())->toBe(['country' => $expectedCountry])
                ->and(array_keys($response->json()))->toBe(['country'])
                ->and(cacheControlDirectives($response->headers->get('Cache-Control')))->toBe([
                    'max-age' => '0',
                    'no-store' => true,
                    'private' => true,
                ])
                ->and($response->headers->all('Vary'))->toBe(['CF-IPCountry']);
        });
});
