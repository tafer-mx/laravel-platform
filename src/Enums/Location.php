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
    }