<?php

namespace TAFER\Core\Services;

use Illuminate\Support\Facades\Log;
use Storyblok\Api\Domain\Value\Dto\Direction;
use Storyblok\Api\Domain\Value\Dto\Pagination;
use Storyblok\Api\Domain\Value\Dto\SortBy;
use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Api\Domain\Value\Slug\Slug;
use Storyblok\Api\Request\StoriesRequest;
use Storyblok\Api\StoriesApiInterface;
use TAFER\Core\Context\RequestCtx;
use Throwable;

/**
 * Lista stories "suites-data" desde la carpeta de entities del resort.
 */
class SuitesEntityService
{
    private const PER_PAGE = 100;

    public function __construct(
        private readonly StoriesApiInterface $stories,
        private readonly RequestCtx $ctx,
    ) {}

    public function all(): array
    {
        try {
            return $this->stories->all(new StoriesRequest(
                language: $this->ctx->locale->value,
                pagination: new Pagination(perPage: self::PER_PAGE),
                sortBy: new SortBy('position', Direction::Asc),
                version: $this->ctx->isPreview ? Version::Draft : Version::Published,
                startsWith: new Slug($this->folder().'/'),
            ))->stories;
        } catch (Throwable $e) {
            Log::error('Suites entities error: '.$e->getMessage());

            return [];
        }
    }

    private function folder(): string
    {
        $path = trim((string) config('tafer.suites.entities_path', 'entities/suites'), '/');

        return "brands/{$this->ctx->resort->value}/{$path}";
    }
}
