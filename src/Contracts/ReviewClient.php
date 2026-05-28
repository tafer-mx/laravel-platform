<?php

namespace TAFER\Core\Contracts;

use Illuminate\Support\Collection;
use TAFER\Core\Dto\ReviewDTO;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Records\ResortRegion;

/**
 * Contract for retrieving hotel and brand reviews from the reviews provider.
 */
interface ReviewClient
{
    /**
     * Get reviews for a specific hotel/resort region.
     *
     * @param ResortRegion $hotel Resort region used to resolve the review code ex: MSPV - GBCN - VPCN.
     * @param Locale $locale Locale used for the request language.
     *
     * @return Collection<int, ReviewDTO> Collection of review DTOs.
     */
    public function getByHotel(ResortRegion $hotel, Locale $locale): Collection;

    /**
     * Get reviews for a specific brand/resort.
     *
     * @param Resort $resort Resort enum used to resolve the brand code. Ex: GB, VP, MS, etc.
     * @param Locale $locale Locale used for the request language.
     *
     * @return Collection<int, ReviewDTO> Collection of review DTOs.
     */
    public function getByBrand(Resort $resort, Locale $locale): Collection;
}