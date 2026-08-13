<?php

namespace TAFER\Core\Enums;

use TAFER\Core\Records\ResortRegion;

enum Resort: string
{
    // These values are managed by storyblok slug manager,
    // if any of them change in the cms, then they must to be changed here as well

    case GarzaBlanca = 'garza-blanca';
    case HotelMousai = 'mousai';
    case VillaPalmarCancun = 'villa-palmar-cancun';
    case Sanctuary = 'sanctuary';

    /**
     * Get the human-readable resort name.
     */
    public function label(): string
    {
        return match ($this) {
            self::GarzaBlanca => 'Garza Blanca',
            self::HotelMousai => 'Hotel Mousai',
            self::VillaPalmarCancun => 'Villa Palmar Cancun',
            self::Sanctuary => 'Sanctuary',
        };
    }

    /**
     * Get the general resort code.
     */
    public function code(): string
    {
        return match ($this) {
            self::GarzaBlanca => 'GB',
            self::HotelMousai => 'MS',
            self::VillaPalmarCancun => 'VP',
            self::Sanctuary => 'SNCTRY',
        };
    }

    /**
     * Get all regions where the resort is available.
     *
     * @return ResortRegion[]
     */
    public function regions(): array
    {
        return match ($this) {
            self::GarzaBlanca => [
                new ResortRegion(Location::Cancun, 'GBCN'),
                new ResortRegion(Location::PuertoVallarta, 'GBPV'),
                new ResortRegion(Location::Cabo, 'GBLC'),
            ],

            self::HotelMousai => [
                new ResortRegion(Location::Cancun, 'MSCN'),
                new ResortRegion(Location::PuertoVallarta, 'MSPV'),
            ],

            self::VillaPalmarCancun => [
                new ResortRegion(Location::Cancun, 'VPCN'),
                new ResortRegion(Location::Corp, 'VPCN'),
            ],

            self::Sanctuary => [
                new ResortRegion(Location::PuertoVallarta, 'SNCTRY'),
            ],
        };
    }

    /**
     * Get the resort code for a specific location.
     */
    public function regionCode(Location $location): ?string
    {
        foreach ($this->regions() as $region) {
            if ($region->location === $location) {
                return $region->code;
            }
        }

        return null;
    }

    /**
     * Check if the resort is available in the given location.
     */
    public function hasRegion(Location $location): bool
    {
        return $this->regionCode($location) !== null;
    }

    /**
     * Get the region record for a specific location.
     */
    public function region(Location $location): ?ResortRegion
    {
        foreach ($this->regions() as $region) {
            if ($region->location === $location) {
                return $region;
            }
        }

        return null;
    }

    public static function resortByRegionCode(string $code): ?self
    {
        $code = strtoupper(trim($code));

        foreach (self::cases() as $resort) {
            foreach ($resort->regions() as $region) {
                if ($region->code === $code) {
                    return $resort;
                }
            }
        }

        return null;
    }

    public function parent(): ?self
    {
        return match ($this) {
            self::Sanctuary => self::GarzaBlanca,
            default => null,
        };
    }
}
