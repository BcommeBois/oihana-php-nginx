<?php

namespace oihana\nginx\helpers ;

/**
 * Reads the directory a vhost serves the ACME challenge from: the `root` of its
 * `location ^~ /.well-known/acme-challenge/` block.
 *
 * Let's Encrypt fetches its token from there, so a certificate can only be asked for, with
 * `certbot --webroot`, through the directory the vhost in place serves. Two shapes are common:
 * the site's own document root, and one directory shared by every site of the machine.
 *
 * Only the `root` written directly in that block counts: not the server's `root` before it, which
 * the block may inherit but does not declare, and not one in a block nested inside it.
 *
 * @param string $vhost The text of a vhost.
 *
 * @return string|null The directory, without a trailing slash; null when no such block sets one.
 *
 * @example
 * ```php
 * use function oihana\nginx\helpers\vhostAcmeRoot;
 *
 * $vhost = <<<NGINX
 * server {
 *     location ^~ /.well-known/acme-challenge/ {
 *         root /var/www/letsencrypt/;
 *     }
 * }
 * NGINX;
 *
 * vhostAcmeRoot( $vhost ) ; // '/var/www/letsencrypt'
 * ```
 *
 * @package oihana\nginx\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   0.1.0
 */
function vhostAcmeRoot( string $vhost ) :?string
{
    $depth  = 0 ;
    $inside = null ;

    foreach ( nginxStatements( $vhost ) as $statement )
    {
        if ( str_ends_with( $statement , '{' ) )
        {
            $depth++ ;

            if ( $inside === null && preg_match( '#^location \^~ /\.well-known/acme-challenge/? \{$#' , $statement ) === 1 )
            {
                $inside = $depth ;
            }

            continue ;
        }

        if ( $statement === '}' )
        {
            if ( $inside === $depth )
            {
                $inside = null ;
            }

            $depth-- ;

            continue ;
        }

        if ( $inside === $depth && preg_match( '/^root (\S+);$/' , $statement , $matched ) === 1 )
        {
            return rtrim( $matched[ 1 ] , '/' ) ;
        }
    }

    return null ;
}
