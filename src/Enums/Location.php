<?php

    namespace TAFER\Core\Enums;

    enum Location: string {

        //These values are managed by storyblok slug manager, 
        //if any of them change in the cms, then they must to be changed here as well

        case Cancun = 'cancun';
        case PuertoVallarta = 'puerto-vallarta';
        case Cabo = 'los-cabos';
        case Corp = 'corp';

        /**
         * Check if this location represents the corporate location.
         */
        function isCorp(): bool {
            return $this === self::Corp;
        }

        /**
         * Get the human-readable location name.
         *
         * @param Locale $lang Language used to return the label.
         *
         * @return string Human-readable location name.
         */
        public function label(Locale $lang = Locale::English): string
        {
            return match ($this) {
                self::Cancun => match ($lang) {
                    Locale::English => 'Cancun',
                    Locale::Spanish => 'Cancún',
                },

                self::PuertoVallarta => match ($lang) {
                    Locale::English,
                    Locale::Spanish => 'Puerto Vallarta',
                },

                self::Cabo => match ($lang) {
                    Locale::English,
                    Locale::Spanish => 'Los Cabos',
                },

                self::Corp => match ($lang) {
                    Locale::English => 'Corporate',
                    Locale::Spanish => 'Corporativo',
                },
            };
        }

        /**
         * Create a Location enum from a slug.
         *
         * @param string|null $locationSlug Location slug from Storyblok.
         *
         * @return self|null Location enum instance, or null if the slug is empty or invalid.
         */
        public static function fromSlug(?string $locationSlug): ?self
        {
            return $locationSlug ? self::tryFrom($locationSlug) : null;
        }
    }