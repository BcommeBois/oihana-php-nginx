<?php

namespace oihana\nginx\helpers ;

/**
 * Reads the name of the certbot certificate a vhost serves: the directory under certbot's `live`
 * directory that its `ssl_certificate` names.
 *
 * Read in the vhost, never composed from the site's name: certbot names a certificate after the
 * first domain it was asked for, or after `--cert-name`, so `example.com` and `www.example.com`
 * may well be served by a certificate called `example.com`, `www.example.com` or anything else.
 * The name is what `certbot renew --cert-name` and `certbot delete --cert-name` expect.
 *
 * Only `ssl_certificate` counts: the key (`ssl_certificate_key`) names the same directory, and a
 * vhost serving a certificate that certbot did not issue gives null.
 *
 * @param string $vhost         The text of a vhost.
 * @param string $liveDirectory certbot's `live` directory, where it keeps one sub-directory of
 *                              links per certificate.
 *
 * @return string|null The certificate's name, or null when the vhost serves no certificate from
 *                     that directory.
 *
 * @example
 * ```php
 * use function oihana\nginx\helpers\vhostCertificateName;
 *
 * vhostCertificateName( 'ssl_certificate /etc/letsencrypt/live/example.com/fullchain.pem;' ) ;
 * // 'example.com'
 *
 * vhostCertificateName( 'ssl_certificate /etc/ssl/certs/site.pem;' ) ;
 * // null
 * ```
 *
 * @package oihana\nginx\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   0.1.0
 */
function vhostCertificateName( string $vhost , string $liveDirectory = '/etc/letsencrypt/live' ) :?string
{
    $live = preg_quote( rtrim( $liveDirectory , '/' ) . '/' , '#' ) ;

    foreach ( nginxStatements( $vhost ) as $statement )
    {
        if ( preg_match( '#^ssl_certificate ' . $live . '([^/;\s]+)/[^;\s]+;$#' , $statement , $matched ) === 1 )
        {
            return $matched[ 1 ] ;
        }
    }

    return null ;
}
