<?php

namespace TAFER\Core\Storyblok;

use Storyblok\Api\Response\StoryResponse;

final readonly class CachedStory
{
    /**
     * @param  array<string, mixed>  $story
     * @param  list<array<string, mixed>>  $links
     */
    public function __construct(
        public array $story,
        public int $cv = 0,
        public array $links = [],
    ) {}

    public static function fromResponse(StoryResponse $response): self
    {
        return new self($response->story, $response->cv, $response->links);
    }

    /**
     * @param  array<string, mixed>  $story
     */
    public static function fromRelation(array $story, int $cv): self
    {
        return new self($story, $cv);
    }

    public function toResponse(): StoryResponse
    {
        return new StoryResponse([
            'story' => $this->story,
            'cv' => $this->cv,
            'rels' => [],
            'rel_uuids' => [],
            'links' => $this->links,
        ]);
    }

    /**
     * @return array{story: array<string, mixed>, cv: int, links: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'story' => $this->story,
            'cv' => $this->cv,
            'links' => $this->links,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): ?self
    {
        if (
            ! isset($payload['story'], $payload['cv'], $payload['links'])
            || ! is_array($payload['story'])
            || ! is_int($payload['cv'])
            || ! is_array($payload['links'])
        ) {
            return null;
        }

        return new self($payload['story'], $payload['cv'], $payload['links']);
    }
}
