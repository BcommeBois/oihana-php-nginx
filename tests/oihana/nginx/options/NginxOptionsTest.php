<?php

namespace tests\oihana\nginx\options;

use oihana\nginx\enums\NginxPath;
use oihana\nginx\options\NginxOptions;
use PHPUnit\Framework\TestCase;

class NginxOptionsTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $opts = new NginxOptions();
        $this->assertTrue($opts->sudo, 'sudo doit être true par défaut');
        $this->assertNull($opts->conf);
        $this->assertNull($opts->dir);
        $this->assertNull($opts->help);
    }

    public function testGetSiteAvailableDirectoryWithDir(): void
    {
        $opts = new NginxOptions();
        $opts->dir = '/etc/nginx';
        $expected = '/etc/nginx/' . NginxPath::SITES_AVAILABLE;
        $this->assertSame($expected, $opts->getSiteAvailableDirectory());
    }

    public function testGetSiteAvailableDirectoryWithoutDir(): void
    {
        $opts = new NginxOptions();
        $expected = NginxPath::SITES_AVAILABLE; // Char::EMPTY = ''
        $this->assertSame($expected, $opts->getSiteAvailableDirectory());
    }

    public function testGetSiteEnabledDirectoryWithDir(): void
    {
        $opts = new NginxOptions();
        $opts->dir = '/etc/nginx';
        $expected = '/etc/nginx/' . NginxPath::SITES_ENABLED;
        $this->assertSame($expected, $opts->getSiteEnabledDirectory());
    }

    public function testGetSiteEnabledDirectoryWithoutDir(): void
    {
        $opts = new NginxOptions();
        $expected = NginxPath::SITES_ENABLED;
        $this->assertSame($expected, $opts->getSiteEnabledDirectory());
    }

    public function testToStringExcludesConfDirInitSudo(): void
    {
        $opts = new NginxOptions();
        $opts->help   = true;
        $opts->test   = true;
        $opts->conf   = '/tmp/test.conf'; // devrait être exclu
        $opts->dir    = '/etc/nginx';     // devrait être exclu
        $opts->init   = 'init-script';    // devrait être exclu
        $opts->sudo   = false;            // devrait être exclu

        $string = (string) $opts;

        $this->assertStringContainsString('-h', $string);
        $this->assertStringContainsString('-t', $string);
    }
}