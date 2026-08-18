<?php

declare(strict_types=1);

namespace tests\oihana\nginx\helpers\blocks ;

use InvalidArgumentException;
use oihana\enums\http\UriScheme;
use oihana\nginx\enums\RedirectDirection;
use PHPUnit\Framework\TestCase;

use function oihana\nginx\helpers\blocks\redirectBlock;

final class RedirectBlockTest extends TestCase
{
    public function testReturnsEmptyStringWhenDomainsEmpty(): void
    {
        $this->assertSame('', redirectBlock( [], 'www'));
        $this->assertSame('', redirectBlock( null, 'www'));
        $this->assertSame('', redirectBlock( [''], 'www'));
    }

    public function testReturnsEmptyStringWhenSubdomainsEmpty(): void
    {
        $this->assertSame('', redirectBlock( ['example.com'] , []));
        $this->assertSame('', redirectBlock( ['example.com'] , null));
        $this->assertSame('', redirectBlock( ['example.com'] , ['']));
    }

    public function testOutboundRedirectSingleDomainAndSubdomain(): void
    {
        $output = redirectBlock('example.com', 'www', RedirectDirection::OUTBOUND, 4);

        $expected = <<<NGINX
    ### Redirect example.com to www.example.com ###
    if (\$host = 'example.com') {
        return 301 https://www.example.com\$request_uri;
    }
NGINX;

        $this->assertStringContainsString($expected, $output);
    }

    public function testInboundRedirectSingleDomainAndSubdomain(): void
    {
        $output = redirectBlock('example.com', 'www', RedirectDirection::INBOUND, 4);

        $expected = <<<NGINX
    ### Redirect www.example.com to example.com ###
    if (\$host = 'www.example.com') {
        return 301 https://example.com\$request_uri;
    }
NGINX;

        $this->assertStringContainsString($expected, $output);
    }

    public function testMultipleDomainsAndSubdomains(): void
    {
        $domains = ['example.com', 'test.org'];
        $subdomains = ['www', 'm'];

        $output = redirectBlock($domains, $subdomains, RedirectDirection::OUTBOUND, 2);

        // Check some expected lines are present for example.com www
        $this->assertStringContainsString("### Redirect example.com to www.example.com ###", $output);
        $this->assertStringContainsString("return 301 https://www.example.com\$request_uri;", $output);

        // Check some expected lines are present for test.org m
        $this->assertStringContainsString("### Redirect test.org to m.test.org ###", $output);
        $this->assertStringContainsString("return 301 https://m.test.org\$request_uri;", $output);
    }

    public function testNoCommentsIfCommentFalse(): void
    {
        $output = redirectBlock('example.com', 'www', RedirectDirection::OUTBOUND, 0, false);

        $this->assertStringNotContainsString('### Redirect', $output);
    }

    /**
     * The scheme was written into the block and could not be asked for.
     *
     * `https` is right for a site behind a certificate and wrong for every site that is not
     * yet: the redirection pointed at an endpoint nothing served, and where the application
     * sends visitors back to its own canonical URL, the two closed a loop.
     */
    public function testTheSchemeDefaultsToHttps(): void
    {
        $this->assertSame
        (
            redirectBlock( 'example.com' , 'www' , RedirectDirection::OUTBOUND , 4 ) ,
            redirectBlock( 'example.com' , 'www' , RedirectDirection::OUTBOUND , 4 , true , UriScheme::HTTPS ) ,
            'Callers that ask for nothing must keep the block they had.'
        ) ;
    }

    public function testAnHttpSchemeIsRendered(): void
    {
        $output = redirectBlock( 'example.com' , 'www' , RedirectDirection::OUTBOUND , 4 , true , UriScheme::HTTP ) ;

        $expected = <<<NGINX
    ### Redirect example.com to www.example.com ###
    if (\$host = 'example.com') {
        return 301 http://www.example.com\$request_uri;
    }
NGINX;

        $this->assertStringContainsString( $expected , $output ) ;
        $this->assertStringNotContainsString( 'https://' , $output ) ;
    }

    public function testAnUnknownSchemeThrowsException(): void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->expectExceptionMessage( 'Invalid redirection scheme : ftp' ) ;

        redirectBlock( 'example.com' , 'www' , RedirectDirection::OUTBOUND , 4 , true , 'ftp' ) ;
    }

    public function testInvalidDirectionThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        redirectBlock('example.com', 'www', 'INVALID_DIRECTION');
    }
}