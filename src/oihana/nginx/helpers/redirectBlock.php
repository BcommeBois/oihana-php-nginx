<?php

namespace oihana\nginx\helpers\blocks ;

use InvalidArgumentException;

use oihana\enums\Char;
use oihana\nginx\enums\RedirectDirection;

use function oihana\core\strings\block;

/**
 * Generate multiple NGINX redirection block (inbound or outbound).
 *
 * @param array|string|null $domains    Root domain(s) like "example.com".
 * @param array|string|null $subdomains Subdomain(s) like "www".
 * @param string           $direction   One of RedirectDirection::INBOUND or ::OUTBOUND.
 * @param string|int       $indent      Indentation (string or number of spaces).
 *
 * @param bool $comment Adds a comment before each block.
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
 *     if ($host ~* ^ooop\\.fr$)
 *     {
 *         rewrite ^(.*) https://www.ooop.fr$1 permanent;
 *         break
 *     }
 *
 *     ### Redirect ooopener.com to www.ooopener.com ###
 *     if ($host ~* ^ooopener\\.com$)
 *     {
 *         rewrite ^(.*) https://www.ooopener.com$1 permanent;
 *         break
 *     }
 *
 *     ### Redirect www.ooop.fr to ooop.fr ###
 *     if ($host ~* ^www\\.ooop\\.fr$)
 *     {
 *         rewrite ^(.*) https://ooop.fr$1 permanent;
 *         break
 *     }
 * ``
 */
function redirectBlock
(
    array|string|null $domains ,
    array|string|null $subdomains = 'www' ,
    string            $direction  = RedirectDirection::OUTBOUND ,
    string|int        $indent     = Char::EMPTY,
    bool              $comment    = true
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
                "    return 301 https://{$to}\$request_uri;",
                "}"
            ];

            $blocks[] = block( $lines , $indent ) ;
        }
    }

    return implode(PHP_EOL . PHP_EOL , $blocks ) ;
}