<?php

namespace TAFER\Core\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

use TAFER\Core\Contracts\ReviewClient;
use TAFER\Core\Records\ResortRegion;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Resort;

class ReviewsService implements ReviewClient 
{


    public function __construct(protected Client $client)
    {}

    public function getByHotel(ResortRegion $hotel, Locale $locale): array
    {
        $hotelCode = $hotel->code;
        try {
            $response = $this->client->get("code/{$hotelCode}", [
                'headers' => [
                    'Accept-Language' => $locale->value,
                ],
                'query' => [
                    'language' => $locale->value,
                ],
            ]);
            $reviews = json_decode($response->getBody()->getContents(), true);

            return $this->filterFiveStarReviews($reviews);
        } catch (ClientException $e) {

            \Log::warning('Reviews API 4xx', [
                'status' => $e->getResponse()?->getStatusCode(),
                'body'   => $e->getResponse()?->getBody()->getContents(),
            ]);
            return [];

        } catch (RequestException $e) {

            \Log::error('Reviews API Error: ' . $e->getMessage());
            return [];
        }
    }

    public function getByBrand(Resort $resort, Locale $locale): array
    {
        $brandCode = $resort->code();
        try {
            $response = $this->client->get("brand/{$brandCode}", [
                'headers' => [
                    'Accept-Language' => $locale->value,
                ],
                'query' => [
                    'language' => $locale->value,
                ],
            ]);
            $reviews = json_decode($response->getBody()->getContents(), true);
            return $this->filterFiveStarReviews($reviews);

        } catch (ClientException $e) {
            \Log::warning('Reviews API 4xx', [
                'status' => $e->getResponse()?->getStatusCode(),
                'body'   => $e->getResponse()?->getBody()->getContents(),
            ]);
            return [];

        } catch (RequestException $e) {

            \Log::error('Reviews API Error: ' . $e->getMessage());
            return [];
        }
    }

    public function filterFiveStarReviews(array $reviews): array
    {
        return array_filter($reviews, function ($review) {
            return isset($review['rating']) && $review['rating'] === 5;
        });
    }
}