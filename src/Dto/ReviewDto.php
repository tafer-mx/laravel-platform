<?php

namespace TAFER\Core\DTO;

use Carbon\CarbonImmutable;

//TODO: Type brand - destination - code - Language and provider if possible
/**
 * Data transfer object representing a single review returned by the reviews API.
 */
final readonly class ReviewDTO
{
    /**
     * @param string|null $externalId External review identifier from the provider.
     * @param string|null $title Review title.
     * @param string|null $excerpt Short review content or summary.
     * @param int $rating Numeric review rating.
     * @param CarbonImmutable|null $publishedAt Date when the review was published.
     * @param string|null $userName Name of the reviewer.
     * @param string|null $avatarUrl Reviewer avatar URL.
     * @param string|null $brand Brand associated with the review.
     * @param string|null $destination Destination associated with the review.
     * @param string|null $code Hotel or resort code associated with the review.
     * @param string|null $language Review language.
     * @param string|null $provider Review provider, for example google or tripadvisor.
     * @param bool $visibility Whether the review is visible.
     */
    public function __construct(
        public ?string $externalId,
        public ?string $title,
        public ?string $excerpt,
        public int $rating,
        public ?CarbonImmutable $publishedAt,
        public ?string $userName,
        public ?string $avatarUrl,
        public ?string $brand,
        public ?string $destination,
        public ?string $code,
        public ?string $language,
        public ?string $provider,
        public bool $visibility,
    ) {}

    /**
     * Create a review DTO from an API response item.
     *
     * @param array{
     *     external_id?: string|null,
     *     title?: string|null,
     *     excerpt?: string|null,
     *     rating?: int|string|null,
     *     published_at?: string|null,
     *     user_name?: string|null,
     *     avatar_url?: string|null,
     *     brand?: string|null,
     *     destination?: string|null,
     *     code?: string|null,
     *     language?: string|null,
     *     provider?: string|null,
     *     visibility?: bool|int|string|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            externalId: $data['external_id'] ?? null,
            title: $data['title'] ?? null,
            excerpt: $data['excerpt'] ?? null,
            rating: (int) ($data['rating'] ?? 0),
            publishedAt: !empty($data['published_at'])
                ? CarbonImmutable::parse($data['published_at'])
                : null,
            userName: $data['user_name'] ?? null,
            avatarUrl: $data['avatar_url'] ?? null,
            brand: $data['brand'] ?? null,
            destination: $data['destination'] ?? null,
            code: $data['code'] ?? null,
            language: $data['language'] ?? null,
            provider: $data['provider'] ?? null,
            visibility: (bool) ($data['visibility'] ?? false),
        );
    }

    /**
     * Convert the review DTO to an array using the API response field names.
     *
     * @return array{
     *     external_id: string|null,
     *     title: string|null,
     *     excerpt: string|null,
     *     rating: int,
     *     published_at: string|null,
     *     user_name: string|null,
     *     avatar_url: string|null,
     *     brand: string|null,
     *     destination: string|null,
     *     code: string|null,
     *     language: string|null,
     *     provider: string|null,
     *     visibility: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'rating' => $this->rating,
            'published_at' => $this->publishedAt?->toISOString(),
            'user_name' => $this->userName,
            'avatar_url' => $this->avatarUrl,
            'brand' => $this->brand,
            'destination' => $this->destination,
            'code' => $this->code,
            'language' => $this->language,
            'provider' => $this->provider,
            'visibility' => $this->visibility,
        ];
    }
}