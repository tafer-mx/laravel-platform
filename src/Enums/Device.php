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
}
