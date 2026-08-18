<?php

namespace oihana\nginx\helpers\blocks ;

use InvalidArgumentException;

use oihana\enums\Char;

use oihana\enums\http\UriScheme;

use oihana\nginx\enums\RedirectDirection;

use function oihana\core\strings\block;

/**
 * Generate multiple NGINX redirection block (inbound or outbound).
 *
 * @param array|string|null $domains    Root domain(s) like "example.com".
 * @param array|string|null $subdomains Subdomain(s) like "www".
 * @param string           $direction   One of RedirectDirection::INBOUND or ::OUTBOUND.
 * @param string|int       $indent      Indentation (string or number of spaces).
 * @param bool             $comment     Adds a comment before each block.
 * @param string           $scheme      One of UriScheme::HTTPS (default) or ::HTTP. A site
 *                                      that is not behind a certificate has to be sent to
 *                                      `http`, or the redirection points at an endpoint nothing
 *                                      serves.
 *
 * @return string Combined NGINX redirection blocks.
 *
 * @example
 * ```php
 * $block = redirectBlock( [ 'ooop.fr' , 'ooopener.com' ], [ 'www' ] , indent: 4 );
 *
 * echo $block . PHP_EOL . PHP_EOL ;
 *
 * $block = redirectBlock( 'ooop.fr' , 'www' , RedirectDirection::INBOUND , 4 );
 *
 * echo $block . PHP_EOL;
 * ```
 *
 * Output:
 * ```
 *     ### Redirect ooop.fr to www.ooop.fr ###
 *     if ($host = 'ooop.fr') {
 *         return 301 https://www.ooop.fr$request_uri;
 *     }
 *
 *     ### Redirect ooopener.com to www.ooopener.com ###
 *     if ($host = 'ooopener.com') {
 *         return 301 https://www.ooopener.com$request_uri;
 *     }
 *
 *     ### Redirect www.ooop.fr to ooop.fr ###
 *     if ($host = 'www.ooop.fr') {
 *         return 301 https://ooop.fr$request_uri;
 *     }
 * ```
 */
function redirectBlock
(
    array|string|null $domains ,
    array|string|null $subdomains = 'www' ,
    string            $direction  = RedirectDirection::OUTBOUND ,
    string|int        $indent     = Char::EMPTY,
    bool              $comment    = true ,
    string            $scheme     = UriScheme::HTTPS
)
: string
{
    static $clean = null ;
    if ( $clean === null )
    {
        $clean = static function( null|array|string $list ): array
        {
            if( $list === null )
            {
                return [] ;
            }
            return array_filter
            (
                array_map( fn( $v ) => is_string($v) ? trim($v) : Char::EMPTY , (array) $list ) ,
                fn( $v ) => $v !== Char::EMPTY
            );
        };
    }

    // UriScheme carries more than these two; a 301 between hosts is only meaningful over
    // http or https, so the restriction belongs here rather than in the enum.
    if ( $scheme !== UriScheme::HTTP && $scheme !== UriScheme::HTTPS )
    {
        throw new InvalidArgumentException( "Invalid redirection scheme : $scheme" ) ;
    }

    $domains    = $clean( $domains    ) ;
    $subdomains = $clean( $subdomains ) ;

    if ( empty( $domains ) || empty( $subdomains ) )
    {
        return Char::EMPTY ;
    }

    if ( is_int( $indent ) )
    {
        $indent = str_repeat(Char::SPACE , $indent ) ;
    }

    $blocks = [];

    foreach ( $domains as $domain )
    {
        foreach ( $subdomains as $subdomain )
        {
            $rootDomain    = $domain ;
            $fullSubdomain = "{$subdomain}.{$domain}" ;

            switch ( $direction )
            {
                case RedirectDirection::OUTBOUND :
                {
                    $from        = $rootDomain ;
                    $to          = $fullSubdomain ;
                    $commentLine = "### Redirect {$from} to {$to} ###" ;
                    break ;
                }

                case RedirectDirection::INBOUND:
                {
                    $from        = $fullSubdomain;
                    $to          = $rootDomain;
                    $commentLine = "### Redirect {$from} to {$to} ###" ;
                    break;
                }

                default:
                {
                    throw new InvalidArgumentException("Invalid redirection direction : $direction" ) ;
                }
            }

            $lines = $comment ? [ $commentLine ] : [] ;
            $lines =
            [
                ...$lines,
                "if (\$host = '{$from}') {",
                "    return 301 {$scheme}://{$to}\$request_uri;",
                "}"
            ];

            $blocks[] = block( $lines , $indent ) ;
        }
    }

    return implode(PHP_EOL . PHP_EOL , $blocks ) ;
}