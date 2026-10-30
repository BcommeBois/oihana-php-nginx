<?php

declare(strict_types=1);

namespace tests\oihana\nginx\helpers ;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function oihana\nginx\helpers\vhostAcmeRoot;

#[CoversFunction('oihana\nginx\helpers\vhostAcmeRoot')]
final class VhostAcmeRootTest extends TestCase
{
    public function testFromTheSitesOwnRoot() :void
    {
        $vhost = <<<'NGINX'
            server
            {
                listen 80;
                server_name example.com www.example.com;
                root /var/www/example.com/htdocs;

                location ^~ /.well-known/acme-challenge/
                {
                    default_type "text/plain";
                    root /var/www/example.com/htdocs;
                }

                location / { return 301 https://$host$request_uri; }
            }
            NGINX ;

        $this->assertSame( '/var/www/example.com/htdocs' , vhostAcmeRoot( $vhost ) ) ;
    }

    public function testFromADirectorySharedByEverySite() :void
    {
        $vhost = <<<'NGINX'
            server {
                listen 80;
                server_name example.com;

                location ^~ /.well-known/acme-challenge/ {
                    allow all;
                    root /var/www/letsencrypt/;
                }
            }
            NGINX ;

        $this->assertSame( '/var/www/letsencrypt' , vhostAcmeRoot( $vhost ) ) ;
    }

    public function testTheLocationMayOmitItsTrailingSlash() :void
    {
        $vhost = "location ^~ /.well-known/acme-challenge { root /srv/acme; }\n" ;

        $this->assertSame( '/srv/acme' , vhostAcmeRoot( $vhost ) ) ;
    }

    /**
     * The server's `root`, before the block, is inherited by it but not declared in it.
     */
    public function testTheServersRootIsNotTheChallenges() :void
    {
        $vhost = "server {\n    root /var/www/site;\n    location ^~ /.well-known/acme-challenge/ {\n        allow all;\n    }\n}\n" ;

        $this->assertNull( vhostAcmeRoot( $vhost ) ) ;
    }

    public function testARootAfterTheBlockIsNotTheChallenges() :void
    {
        $vhost = "server {\n    location ^~ /.well-known/acme-challenge/ { allow all; }\n    root /var/www/site;\n}\n" ;

        $this->assertNull( vhostAcmeRoot( $vhost ) ) ;
    }

    /**
     * Once the challenge's block is closed, a block opened at the same depth is another one.
     */
    public function testALaterBlockAtTheSameDepthIsNotTheChallenges() :void
    {
        $vhost = "location ^~ /.well-known/acme-challenge/ { allow all; }\nlocation /x { root /wrong; }\n" ;

        $this->assertNull( vhostAcmeRoot( $vhost ) ) ;
    }

    public function testANestedBlockIsNotTheChallenges() :void
    {
        $vhost = "location ^~ /.well-known/acme-challenge/ {\n    location ~ x { root /elsewhere; }\n    root /var/www/letsencrypt/;\n}\n" ;

        $this->assertSame( '/var/www/letsencrypt' , vhostAcmeRoot( $vhost ) ) ;
    }

    public function testAnotherLocationIsNotTheChallenges() :void
    {
        $vhost = "location ~ /\\.well-known/acme-challenge/ { root /wrong; }\nlocation ^~ /.well-known/acme-challenge/ { root /right; }\n" ;

        $this->assertSame( '/right' , vhostAcmeRoot( $vhost ) ) ;
    }

    public function testACommentedRootIsNotTheChallenges() :void
    {
        $vhost = "location ^~ /.well-known/acme-challenge/ {\n    # root /old;\n}\n" ;

        $this->assertNull( vhostAcmeRoot( $vhost ) ) ;
    }

    public function testAVhostWithoutTheBlock() :void
    {
        $this->assertNull( vhostAcmeRoot( "server { listen 443 ssl; root /var/www/x; }\n" ) ) ;
    }
}
