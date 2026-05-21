<?php 

namespace TAFER\Core\Contracts;

use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Records\ResortRegion;

interface ReviewClient {
    //TODO: Type correctly the response of these methods, 
    // currently we are just returning an array of reviews, but we should create a specific record DTO for this
    public function getByHotel(ResortRegion $hotel, Locale $locale): array;
    public function getByBrand(Resort $resort, Locale $locale): array;
}