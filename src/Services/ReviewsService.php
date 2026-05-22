<?php

namespace TAFER\Core\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;
use TAFER\Core\Contracts\ReviewClient;
use TAFER\Core\DTO\ReviewDTO;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Records\ResortRegion;

class ReviewsService implements ReviewClient
{
    public function __construct(protected Client $client)
    {}

    /**
     * @return Collection<ReviewDTO>
     */
    public function getByHotel(ResortRegion $hotel, Locale $locale): Collection
    {
        return $this->getReviews("code/{$hotel->code}", $locale);
    }

    /**
     * @return Collection<ReviewDTO>
     */
    public function getByBrand(Resort $resort, Locale $locale): Collection
    {
        return $this->getReviews("brand/{$resort->code()}", $locale);
    }

    /**
     * Fetch reviews from the given endpoint and map them into DTOs.
     *
     * @return Collection<int, ReviewDTO>
     */
    private function getReviews(string $endpoint, Locale $locale): Collection
    {
        try {
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Accept-Language' => $locale->value,
                ],
                'query' => [
                    'language' => $locale->value,
                ],
            ]);

            $payload = json_decode($response->getBody()->getContents(), true);

            return collect($this->filterReviews($payload['data']))
                ->map(fn (array $review): ReviewDTO => ReviewDTO::fromArray($review))
                ->values();

        } catch (ClientException $e) {
            \Log::warning('Reviews API 4xx', [
                'status' => $e->getResponse()?->getStatusCode(),
                'body'   => $e->getResponse()?->getBody()->getContents(),
            ]);

            return collect();
        } catch (RequestException $e) {
            \Log::error('Reviews API Error: ' . $e->getMessage());

            return collect();
        }
    }

   
    /**
     * Keep only five-star and visible reviews.
     *
     * @param array<int, array<string, mixed>> $reviews
     * @return array<int, array<string, mixed>>
     */
    private function filterReviews(array $reviews): array
    {
        return array_values(array_filter($reviews, fn(array $review): bool => (!empty($review['rating']) && $review['rating'] === 5) 
            && (!empty($review['visibility']) && $review['visibility'] >= 1)));
    }
}