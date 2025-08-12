<?php

declare(strict_types=1);

namespace tests\oihana\nginx\helpers\enums ;

use oihana\nginx\enums\NginxSignal;
use oihana\reflections\exceptions\ConstantException;
use PHPUnit\Framework\TestCase;

final class NginxSignalTest extends TestCase
{
    protected function setUp(): void
    {
        NginxSignal::resetCaches();
    }

    public function testConstantsValues(): void
    {
        $this->assertSame('quit',   NginxSignal::QUIT);
        $this->assertSame('reload', NginxSignal::RELOAD);
        $this->assertSame('reopen', NginxSignal::REOPEN);
        $this->assertSame('stop',   NginxSignal::STOP);
    }

    public function testGetAllReturnsAllConstants(): void
    {
        $expected = [
            'QUIT'   => 'quit',
            'RELOAD' => 'reload',
            'REOPEN' => 'reopen',
            'STOP'   => 'stop',
        ];
        $this->assertSame($expected, NginxSignal::getAll());
    }

    public function testEnumsReturnsSortedUniqueValues(): void
    {
        $enums = NginxSignal::enums();
        $this->assertSame(['quit', 'reload', 'reopen', 'stop'], $enums);
    }

    public function testGetReturnsValueIfValid(): void
    {
        $this->assertSame('quit', NginxSignal::get('quit', 'default'));
    }

    public function testGetReturnsDefaultIfInvalid(): void
    {
        $this->assertSame('default', NginxSignal::get('invalid', 'default'));
    }

    public function testIncludesReturnsTrueForValidValue(): void
    {
        $this->assertTrue(NginxSignal::includes('stop'));
    }

    public function testIncludesReturnsFalseForInvalidValue(): void
    {
        $this->assertFalse(NginxSignal::includes('not-exist'));
    }

    public function testGetConstantReturnsConstantName(): void
    {
        $this->assertSame('RELOAD', NginxSignal::getConstant('reload'));
    }

    public function testGetConstantReturnsNullIfNoMatch(): void
    {
        $this->assertNull(NginxSignal::getConstant('invalid'));
    }

    public function testValidateDoesNotThrowForValidValue(): void
    {
        $this->expectNotToPerformAssertions();
        NginxSignal::validate('stop');
    }

    public function testValidateThrowsForInvalidValue(): void
    {
        $this->expectException(ConstantException::class);
        NginxSignal::validate('nope');
    }

    public function testResetCachesDoesNotBreakGetAll(): void
    {
        $valuesBefore = NginxSignal::getAll();
        NginxSignal::resetCaches();
        $valuesAfter = NginxSignal::getAll();
        $this->assertSame($valuesBefore, $valuesAfter);
    }
}