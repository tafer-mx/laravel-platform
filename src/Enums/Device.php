<?php

namespace TAFER\Core\Enums;

/**
 * Device type used to resolve the default phone number.
 */
enum Device: string
{
    /**
     * Desktop visitors.
     */
    case Desktop = 'desktop';

    /**
     * Mobile visitors.
     */
    case Mobile = 'mobile';

    /**
     * Tablet visitors.
     */
    case Tablet = 'tablet';

    public function isMobile(): bool
    {
        return $this === self::Mobile || $this === self::Tablet;
    }

    public function isDesktop(): bool
    {
        return $this === self::Desktop;
    }
}
