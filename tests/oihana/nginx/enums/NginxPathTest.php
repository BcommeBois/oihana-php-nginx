<?php

declare(strict_types=1);

namespace tests\oihana\nginx\helpers\enums ;

use oihana\nginx\enums\NginxPath;
use oihana\reflections\exceptions\ConstantException;
use PHPUnit\Framework\TestCase;

final class NginxPathTest extends TestCase
{
    protected function setUp(): void
    {
        NginxPath::resetCaches();
    }

    public function testConstantsValues(): void
    {
        $this->assertSame('sites-available', NginxPath::SITES_AVAILABLE);
        $this->assertSame('sites-enabled', NginxPath::SITES_ENABLED);
        $this->assertSame('snippets', NginxPath::SNIPPETS);
    }

    public function testGetAllReturnsAllConstants(): void
    {
        $expected = [
            'SITES_AVAILABLE' => 'sites-available',
            'SITES_ENABLED'   => 'sites-enabled',
            'SNIPPETS'        => 'snippets',
        ];
        $this->assertSame($expected, NginxPath::getAll());
    }

    public function testEnumsReturnsSortedUniqueValues(): void
    {
        $enums = NginxPath::enums();
        $this->assertSame(
            ['sites-available', 'sites-enabled', 'snippets'],
            $enums
        );
    }

    public function testGetReturnsValueIfValid(): void
    {
        $this->assertSame('sites-enabled', NginxPath::get('sites-enabled', 'default'));
    }

    public function testGetReturnsDefaultIfInvalid(): void
    {
        $this->assertSame('default', NginxPath::get('invalid', 'default'));
    }

    public function testIncludesReturnsTrueForValidValue(): void
    {
        $this->assertTrue(NginxPath::includes('snippets'));
    }

    public function testIncludesReturnsFalseForInvalidValue(): void
    {
        $this->assertFalse(NginxPath::includes('not-exist'));
    }

    public function testGetConstantReturnsConstantName(): void
    {
        $this->assertSame('SITES_AVAILABLE', NginxPath::getConstant('sites-available'));
    }

    public function testGetConstantReturnsNullIfNoMatch(): void
    {
        $this->assertNull(NginxPath::getConstant('invalid'));
    }

    public function testValidateDoesNotThrowForValidValue(): void
    {
        $this->expectNotToPerformAssertions();
        NginxPath::validate('sites-enabled');
    }

    public function testValidateThrowsForInvalidValue(): void
    {
        $this->expectException(ConstantException::class);
        NginxPath::validate('nope');
    }

    public function testResetCachesDoesNotBreakGetAll(): void
    {
        $valuesBefore = NginxPath::getAll();
        NginxPath::resetCaches();
        $valuesAfter = NginxPath::getAll();
        $this->assertSame($valuesBefore, $valuesAfter);
    }
}