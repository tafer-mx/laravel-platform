<?php

namespace TAFER\Core\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class RecaptchaService
{
    public function verify(
        string $token,
        string $ip,
        string $expectedAction,
        float $minScore = 0.5
    ): void {
        if (empty($token)) {
            abort(422, 'reCAPTCHA token missing');
        }

        $response = Http::asForm()->post(
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'secret' => config('services.recaptcha.secret'),
                'response' => $token,
                'remoteip' => $ip,
            ]
        )->json();

        Log::info('reCAPTCHA verification', [
            'success' => $response['success'] ?? false,
            'score' => $response['score'] ?? null,
            'action' => $response['action'] ?? null,
            'ip' => $ip,
        ]);

        if (! ($response['success'] ?? false)) {
            abort(422, 'reCAPTCHA failed');
        }

        if ($expectedAction && ($response['action'] ?? null) !== $expectedAction) {
            abort(422, 'reCAPTCHA action mismatch');
        }

        if (($response['score'] ?? 0) < $minScore) {
            abort(422, 'reCAPTCHA score too low');
        }
    }
}
