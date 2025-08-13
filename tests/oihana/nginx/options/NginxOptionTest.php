<?php

namespace tests\oihana\nginx\options;

use oihana\nginx\options\NginxOption;
use oihana\reflect\exceptions\ConstantException;

use PHPUnit\Framework\TestCase;

class NginxOptionTest extends TestCase
{
    protected function setUp(): void
    {
        NginxOption::resetCaches();
    }

    public function testEnumsReturnsAllValuesSorted()
    {
        $enums = NginxOption::enums();
        $expected =
        [
            // ---- extras
            'conf',
            'dir',
            'enabled',
            'init',
            'logs',
            'sudo',
            // ---- Common
            'config',
            'error',
            'global',
            'help',
            'prefix',
            'quiet',
            'signal',
            'test',
            'testFull',
            'version',
            'versionFull',
        ];
        sort($expected, SORT_STRING);
        $this->assertSame($expected, $enums);
    }

    public function testGetCommandOptionReturnsExpectedFlags()
    {
        $map = [
            NginxOption::CONFIG       => 'c',
            NginxOption::ERROR        => 'e',
            NginxOption::GLOBAL       => 'g',
            NginxOption::HELP         => 'h',
            NginxOption::PREFIX       => 'p',
            NginxOption::QUIET        => 'q',
            NginxOption::SIGNAL       => 's',
            NginxOption::TEST         => 't',
            NginxOption::TEST_FULL    => 'T',
            NginxOption::VERSION      => 'v',
            NginxOption::VERSION_FULL => 'V',
        ];

        foreach ($map as $option => $expectedFlag) {
            $this->assertSame($expectedFlag, NginxOption::getCommandOption($option));
        }
    }

    public function testIncludesRecognizesValidAndInvalidValues()
    {
        $this->assertTrue(NginxOption::includes(NginxOption::CONFIG));
        $this->assertFalse(NginxOption::includes('nonexistent'));
    }

    public function testGetReturnsValueOrDefault()
    {
        $this->assertSame(NginxOption::HELP, NginxOption::get(NginxOption::HELP, 'default'));
        $this->assertSame('default', NginxOption::get('nonexistent', 'default'));
    }

    public function testGetConstantReturnsConstantName()
    {
        $this->assertSame('CONFIG', NginxOption::getConstant('config'));
        $this->assertSame('TEST_FULL', NginxOption::getConstant('testFull'));
        $this->assertNull(NginxOption::getConstant('nonexistent'));
    }

    public function testValidateAcceptsValidValue()
    {
        $this->expectNotToPerformAssertions();
        NginxOption::validate(NginxOption::VERSION);
    }

    /**
     * @throws \oihana\reflections\exceptions\ConstantException
     */
    public function testValidateThrowsForInvalidValue()
    {
        $this->expectException(ConstantException::class);
        NginxOption::validate('nonexistent');
    }
}