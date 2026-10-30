<?php

declare(strict_types=1);

namespace tests\oihana\nginx\helpers ;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function oihana\nginx\helpers\vhostCertificateName;

#[CoversFunction('oihana\nginx\helpers\vhostCertificateName')]
final class VhostCertificateNameTest extends TestCase
{
    /**
     * @return array<string, array{string, ?string}>
     */
    public static function vhostProvider() :array
    {
        return
        [
            'named after the bare domain' =>
            [
                "server {\n    ssl_certificate         /etc/letsencrypt/live/example.com/fullchain.pem;\n    ssl_certificate_key     /etc/letsencrypt/live/example.com/privkey.pem;\n}\n" ,
                'example.com' ,
            ] ,
            'named after something else' =>
            [
                "ssl_certificate /etc/letsencrypt/live/example.com.www/fullchain.pem;\n" ,
                'example.com.www' ,
            ] ,
            'certbot\'s numbered name' =>
            [
                "ssl_certificate /etc/letsencrypt/live/example.com-0001/fullchain.pem;\n" ,
                'example.com-0001' ,
            ] ,
            'the first of two' =>
            [
                "ssl_certificate /etc/letsencrypt/live/one/fullchain.pem;\nssl_certificate /etc/letsencrypt/live/two/fullchain.pem;\n" ,
                'one' ,
            ] ,
            'the key alone is not the certificate' =>
            [
                "ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;\n" ,
                null ,
            ] ,
            'a commented line serves nothing' =>
            [
                "# ssl_certificate /etc/letsencrypt/live/old/fullchain.pem;\n" ,
                null ,
            ] ,
            'a certificate that is not certbot\'s' =>
            [
                "ssl_certificate /etc/ssl/certs/site.pem;\n" ,
                null ,
            ] ,
            'the live directory itself names no certificate' =>
            [
                "ssl_certificate /etc/letsencrypt/live/fullchain.pem;\n" ,
                null ,
            ] ,
            'an HTTP vhost' =>
            [
                "server { listen 80; root /var/www/x; }\n" ,
                null ,
            ] ,
        ] ;
    }

    #[DataProvider('vhostProvider')]
    public function testTheName( string $vhost , ?string $expected ) :void
    {
        $this->assertSame( $expected , vhostCertificateName( $vhost ) ) ;
    }

    public function testAnotherLiveDirectory() :void
    {
        $vhost = "ssl_certificate /opt/certbot/live/example.com/fullchain.pem;\n" ;

        $this->assertNull( vhostCertificateName( $vhost ) ) ;
        $this->assertSame( 'example.com' , vhostCertificateName( $vhost , '/opt/certbot/live' ) ) ;
        $this->assertSame( 'example.com' , vhostCertificateName( $vhost , '/opt/certbot/live/' ) ) ;
    }

    /**
     * The directory is matched as text, so a character special in a regular expression must
     * count for itself.
     */
    public function testTheLiveDirectoryIsTakenLiterally() :void
    {
        $vhost = "ssl_certificate /etc/le+ssl/live/example.com/fullchain.pem;\n" ;

        $this->assertSame( 'example.com' , vhostCertificateName( $vhost , '/etc/le+ssl/live' ) ) ;
        $this->assertNull( vhostCertificateName( "ssl_certificate /etc/leessl/live/example.com/fullchain.pem;\n" , '/etc/le+ssl/live' ) ) ;
    }
}
