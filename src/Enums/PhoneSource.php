<?php

namespace TAFER\Core\Enums;

/**
 * Phone tracking source.
 *
 * These values map to external campaign/source identifiers.
 */
enum PhoneSource: string
{
    /**
     * Email source.
     */
    case Email = '8926';

    /**
     * FADS source.
     */
    case Fads = '8929';

    /**
     * GADS source.
     */
    case Gads = '8932';

    /**
     * Get the human-readable source label.
     *
     * @return string Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Fads => 'FADS',
            self::Gads => 'GADS',
        };
    }
}
