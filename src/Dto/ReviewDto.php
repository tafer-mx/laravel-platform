<?php

namespace TAFER\Core\Dto;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Records\ResortRegion;
use TAFER\Core\Enums\Location;

/**
 * Data transfer object representing a single review returned by the reviews API.
 *
 * This DTO normalizes raw review data into strongly typed values.
 */
final readonly class ReviewDto
{
    /**
     * @param int $id Internal review identifier.
     * @param string|null $externalId External review identifier from the provider.
     * @param string|null $title Review title.
     * @param string $excerpt Review content or excerpt.
     * @param int $rating Review rating value.
     * @param CarbonImmutable $publishedAt Date when the review was published.
     * @param string|null $userName Name of the review author.
     * @param string|null $avatarUrl URL of the review author's avatar.
     * @param Resort $brand Resort brand resolved from the region code.
     * @param string $code Region code resolved from the brand and destination.
     * @param Locale $language Review language.
     * @param Location $destination Review destination/location.
     * @param CarbonImmutable $createdAt Date when the review record was created.
     * @param CarbonImmutable $updatedAt Date when the review record was last updated.
     * @param string $provider Review provider name.
     * @param int $visibility Review visibility flag.
     */
    private function __construct(
        public int $id,
        public ?string $externalId,
        public ?string $title,
        public string $excerpt,
        public int $rating,
        public CarbonImmutable $publishedAt,
        public ?string $userName,
        public ?string $avatarUrl,
        public Resort $brand,
        public string $code,
        public Locale $language,
        public Location $destination,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public string $provider,
        public int $visibility,
    ) {}

    /**
     * Create a review DTO from an API response item.
     *
     * The method resolves the review brand from the region code, validates the
     * destination, normalizes the locale, and casts scalar values to the DTO's
     * expected types.
     *
     * @param array{
     *     id: int,
     *     external_id?: string|null,
     *     title?: string|null,
     *     excerpt: string,
     *     rating: int,
     *     published_at: string,
     *     user_name?: string|null,
     *     avatar_url?: string|null,
     *     brand: string,
     *     destination: string,
     *     code: string,
     *     language?: string,
     *     created_at: string,
     *     updated_at: string,
     *     provider: string,
     *     visibility?: int|string
     * } $data Raw review payload.
     *
     * @throws \InvalidArgumentException When the brand, destination, or region code cannot be resolved.
     *
     * @return self
     */
   public static function fromArray(array $data): self
    {
        $brand = !empty($data['code'])
            ? Resort::resortByRegionCode($data['code'])
            : null;
        if (!$brand) {
            throw new \InvalidArgumentException(
                "Invalid or missing brand value: {$data['brand']}"
            );
        }
    

        $location = !empty($data['destination'])
            ? Location::fromCode($data['destination'])
            : null;

        if (!$location) {
            throw new \InvalidArgumentException(
                "Invalid or missing destination value: {$data['destination']}"
            );
        }

        $destination = $brand->regionCode($location);
        if(!$destination) {
            throw new \InvalidArgumentException(
                "Invalid or missing destination value: {$data['destination']}"
            );
        }

        $lang = !empty($data['language'])
            ? Locale::tryFrom($data['language'])
            : Locale::English;

        return new self(
            id: $data['id'],
            externalId: $data['external_id'] ?? null,
            title: $data['title'] ?? null,
            excerpt: $data['excerpt'],
            rating: (int)$data['rating'],
            publishedAt: CarbonImmutable::parse($data['published_at']),
            userName: $data['user_name'] ?? null,
            avatarUrl: $data['avatar_url'] ?? null,
            brand: $brand,
            code: $destination,
            language: $lang ?? Locale::English,
            destination: $location,
            createdAt: CarbonImmutable::parse($data['created_at']),
            updatedAt: CarbonImmutable::parse($data['updated_at']),
            provider: $data['provider'],
            visibility: (int) ($data['visibility'] ?? 1),
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
            'id' => $this->id,
            'external_id' => $this->externalId,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'rating' => $this->rating,
            'published_at' => $this->publishedAt->toISOString(),
            'user_name' => $this->userName,
            'avatar_url' => $this->avatarUrl,
            'brand' => $this->brand->code(),
            'code' => $this->code,
            'language' => $this->language->value,
            'destination' => $this->destination->code(),
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String(),
            'provider' => $this->provider,
            'visibility' => $this->visibility,
        ];
    }
}