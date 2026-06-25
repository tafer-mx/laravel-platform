<?php

namespace TAFER\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Storyblok\StoryblokInvalidationResult;
use TAFER\Core\Storyblok\StoryblokWebhookInvalidator;

final readonly class StoryblokWebhookController
{
    public function __invoke(
        Request $request,
        StoryblokWebhookInvalidator $invalidator,
    ): JsonResponse {
        $data = $request->validate([
            'text' => ['sometimes', 'string'],
            'action' => ['sometimes', 'string'],
            'space_id' => ['sometimes', 'integer'],
            'story_id' => ['sometimes', 'integer'],
            'full_slug' => ['required', 'string'],
            'full_slug__i18n__es' => ['sometimes', 'string'],
        ]);

        $bothLocales = array_key_exists('full_slug__i18n__es', $data);
        $locales = $bothLocales
            ? [Locale::English, Locale::Spanish]
            : [$this->localeFromFullSlug($data['full_slug'])];

        $results = $invalidator->invalidateLocales(
            $data['full_slug'],
            $locales,
        );

        return response()->json([
            'message' => 'Cache invalidated',
            'story_id' => $data['story_id'] ?? null,
            'space_id' => $data['space_id'] ?? null,
            'slug' => $data['full_slug'],
            'action' => $data['action'] ?? null,
            'both_languages' => $bothLocales,
            'languages' => array_map(
                static fn (Locale $locale): string => $locale->value,
                $locales,
            ),
            'delete_results' => array_map(
                fn (StoryblokInvalidationResult $result): array => $this->serializeResult($result),
                $results,
            ),
        ]);
    }

    private function localeFromFullSlug(string $fullSlug): Locale
    {
        return str_starts_with(trim($fullSlug, '/'), 'es/')
            ? Locale::Spanish
            : Locale::English;
    }

    private function serializeResult(StoryblokInvalidationResult $result): array
    {
        return [
            'payload_existed' => $result->payloadExisted,
            'payload_forgotten' => $result->payloadForgotten,
            'uuid_index_existed' => $result->uuidIndexExisted,
            'uuid_index_forgotten' => $result->uuidIndexForgotten,
            'slug_index_existed' => $result->slugIndexExisted,
            'slug_index_forgotten' => $result->slugIndexForgotten,
            'successful' => $result->successful(),
        ];
    }
}
