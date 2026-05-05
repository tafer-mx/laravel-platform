<?php

namespace TAFER\Core\Enums;

enum Locale: string
{
    /**
     * English locale.
     */
    case English = 'en';

    /**
     * Spanish locale.
     */
    case Spanish = 'es';

    /**
     * Get the human-readable locale name.
     *
     * @return string Human-readable locale name.
     */
    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Spanish => 'Spanish',
        };
    }
}