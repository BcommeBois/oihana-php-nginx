<?php

namespace oihana\nginx\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of the important Nginx paths.
 */
class NginxPath
{
    use ConstantsTrait ;

    /**
     * The 'sites-available' path.
     */
    public const string SITES_AVAILABLE = 'sites-available'   ;

    /**
     * The 'sites-enabled' path.
     */
    public const string SITES_ENABLED = 'sites-enabled' ;

    /**
     * The 'snippets' path.
     */
    public const string SNIPPETS = 'snippets' ;
}