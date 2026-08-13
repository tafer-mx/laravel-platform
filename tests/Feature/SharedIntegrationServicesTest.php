<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TAFER\Core\Services\HubSpotMiddlewareService;
use TAFER\Core\Services\RecaptchaService;

uses(TestCase::class);

it('verifies a valid recaptcha token', function () {
    config(['services.recaptcha.secret' => 'recaptcha-secret']);
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
            'score' => 0.9,
            'action' => 'contact_form',
        ]),
    ]);

    (new RecaptchaService)->verify('token', '203.0.113.10', 'contact_form');

    Http::assertSent(fn ($request) => $request->isForm()
        && $request['secret'] === 'recaptcha-secret'
        && $request['response'] === 'token'
        && $request['remoteip'] === '203.0.113.10');
});

it('rejects a missing recaptcha token', function () {
    (new RecaptchaService)->verify('', '203.0.113.10', 'contact_form');
})->throws(HttpException::class, 'reCAPTCHA token missing');

it('rejects a failed recaptcha response', function () {
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false]),
    ]);

    (new RecaptchaService)->verify('token', '203.0.113.10', 'contact_form');
})->throws(HttpException::class, 'reCAPTCHA failed');

it('rejects a recaptcha action mismatch', function () {
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
            'score' => 0.9,
            'action' => 'other_form',
        ]),
    ]);

    (new RecaptchaService)->verify('token', '203.0.113.10', 'contact_form');
})->throws(HttpException::class, 'reCAPTCHA action mismatch');

it('rejects a recaptcha score below the threshold', function () {
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
            'score' => 0.4,
            'action' => 'contact_form',
        ]),
    ]);

    (new RecaptchaService)->verify('token', '203.0.113.10', 'contact_form');
})->throws(HttpException::class, 'reCAPTCHA score too low');

it('creates a HubSpot contact through the shared middleware', function () {
    config([
        'services.middleware.hubspot_endpoint' => 'https://middleware.example/hubspot',
        'services.middleware.mail_token' => 'mail-token',
    ]);
    Http::fake([
        'https://middleware.example/hubspot' => Http::response(['id' => 'contact-id']),
    ]);

    $result = (new HubSpotMiddlewareService)->createContact([
        'email' => 'guest@example.com',
    ]);

    expect($result)->toBe([
        'success' => true,
        'data' => ['id' => 'contact-id'],
    ]);

    Http::assertSent(fn ($request) => $request->hasHeader('X-Mail-Token', 'mail-token')
        && $request['fields'] === ['email' => 'guest@example.com']);
});

it('returns a failure when the HubSpot middleware rejects the request', function () {
    config([
        'services.middleware.hubspot_endpoint' => 'https://middleware.example/hubspot',
        'services.middleware.mail_token' => 'mail-token',
    ]);
    Http::fake([
        'https://middleware.example/hubspot' => Http::response('invalid contact', 422),
    ]);

    $result = (new HubSpotMiddlewareService)->createContact([]);

    expect($result)->toBe([
        'success' => false,
        'error' => 'invalid contact',
    ]);
});

it('returns a failure when the HubSpot middleware throws an exception', function () {
    config([
        'services.middleware.hubspot_endpoint' => 'https://middleware.example/hubspot',
        'services.middleware.mail_token' => 'mail-token',
    ]);
    Log::spy();
    Http::fake(fn () => throw new RuntimeException('network unavailable'));

    $result = (new HubSpotMiddlewareService)->createContact([]);

    expect($result)->toBe([
        'success' => false,
        'error' => 'network unavailable',
    ]);
});
