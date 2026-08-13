<?php

namespace TAFER\Core\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class HubSpotMiddlewareService
{
    protected string $endpoint;

    protected string $token;

    public function __construct()
    {
        $this->endpoint = config('services.middleware.hubspot_endpoint');
        $this->token = config('services.middleware.mail_token');
    }

    public function createContact(array $hubspotFields): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Mail-Token' => $this->token,
            ])->post($this->endpoint, [
                'fields' => $hubspotFields,
            ]);

            if (! $response->successful()) {
                Log::warning('HubSpot middleware error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => $response->body(),
                ];
            }

            return [
                'success' => true,
                'data' => $response->json(),
            ];
        } catch (Throwable $exception) {
            Log::error('HubSpot middleware exception', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }
}
