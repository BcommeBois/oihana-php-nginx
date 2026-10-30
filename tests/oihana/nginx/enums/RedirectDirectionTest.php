<?php

declare(strict_types=1);

namespace tests\oihana\nginx\helpers\enums ;

use oihana\nginx\enums\RedirectDirection;
use oihana\reflect\exceptions\ConstantException;
use PHPUnit\Framework\TestCase;

final class RedirectDirectionTest extends TestCase
{
    protected function setUp(): void
    {
        RedirectDirection::resetCaches();
    }

    public function testConstantsValues(): void
    {
        $this->assertSame('inbound',  RedirectDirection::INBOUND);
        $this->assertSame('outbound', RedirectDirection::OUTBOUND);
    }

    public function testGetAllReturnsAllConstants(): void
    {
        $expected = [
            'INBOUND'  => 'inbound',
            'OUTBOUND' => 'outbound',
        ];
        $this->assertSame($expected, RedirectDirection::getAll());
    }

    public function testEnumsReturnsSortedUniqueValues(): void
    {
        $enums = RedirectDirection::enums();
        $this->assertSame(['inbound', 'outbound'], $enums);
    }

    public function testGetReturnsValueIfValid(): void
    {
        $this->assertSame('inbound', RedirectDirection::get('inbound', 'default'));
    }

    public function testGetReturnsDefaultIfInvalid(): void
    {
        $this->assertSame('default', RedirectDirection::get('invalid', 'default'));
    }

    public function testIncludesReturnsTrueForValidValue(): void
    {
        $this->assertTrue(RedirectDirection::includes('outbound'));
    }

    public function testIncludesReturnsFalseForInvalidValue(): void
    {
        $this->assertFalse(RedirectDirection::includes('not-exist'));
    }

    public function testGetConstantReturnsConstantName(): void
    {
        $this->assertSame('OUTBOUND', RedirectDirection::getConstant('outbound'));
    }

    public function testGetConstantReturnsNullIfNoMatch(): void
    {
        $this->assertNull(RedirectDirection::getConstant('invalid'));
    }

    public function testValidateDoesNotThrowForValidValue(): void
    {
        $this->expectNotToPerformAssertions();
        RedirectDirection::validate('inbound');
    }

    public function testValidateThrowsForInvalidValue(): void
    {
        $this->expectException(ConstantException::class);
        RedirectDirection::validate('nope');
    }

    public function testResetCachesDoesNotBreakGetAll(): void
    {
        $valuesBefore = RedirectDirection::getAll();
        RedirectDirection::resetCaches();
        $valuesAfter = RedirectDirection::getAll();
        $this->assertSame($valuesBefore, $valuesAfter);
    }
}