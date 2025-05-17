<?php

namespace Tests\Unit\Option\Predis;

use Lihs\RedisExclusive\Clients\Option\Predis\SetAdaptor;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('unit')]
#[Group('option')]
#[Group('predis')]
#[CoversNothing]
final class SetAdaptorTest extends TestCase
{
    /**
     * Provides input options and their expected [expireResolution, ttl, flag].
     *
     * @return \Generator<string, array{options: array<string, mixed>, expected: array{null|int, null|int, null|string}}>
     */
    public static function provideOptionsAndexpected(): \Generator
    {
        yield 'EX' => [
            'options' => ['EX' => 10],
            'expected' => [0, 10, null],
        ];

        yield 'PX' => [
            'options' => ['PX' => 2000],
            'expected' => [1, 2000, null],
        ];

        yield 'NX is true' => [
            'options' => ['NX' => true],
            'expected' => [null, null, 'NX'],
        ];

        yield 'NX is false' => [
            'options' => ['NX' => false],
            'expected' => [null, null, null],
        ];

        yield 'XX is true' => [
            'options' => ['XX' => true],
            'expected' => [null, null, 'XX'],
        ];

        yield 'GET is true' => [
            'options' => ['GET' => true],
            'expected' => [null, null, 'GET'],
        ];

        yield 'all options' => [
            'options' => [
                'PX' => 3000,
                'NX' => true,
            ],
            'expected' => [1, 3000, 'NX'],
        ];

        yield 'empty options' => [
            'options' => [],
            'expected' => [null, null, null],
        ];
    }

    #[TestDox('support should return true for SET command')]
    public function testSupportReturnsTrueForSetCommand(): void
    {
        $adaptor = new SetAdaptor();

        $this->assertTrue($adaptor->support('SET'));
    }

    #[TestDox('support should return false for unsupported command')]
    public function testSupportReturnsFalseForUnsupportedCommand(): void
    {
        $adaptor = new SetAdaptor();

        $this->assertFalse($adaptor->support('GET'));
    }

    /**
     * @template T of array{
     *    EX?: int,
     *    PX?: int,
     *    NX?: bool,
     *    XX?: bool,
     *    EXAT?: int,
     *    PXAT?: int,
     *    KEEPTTL?: bool,
     *    GET?: bool,
     * }
     *
     * @param T                                      $options
     * @param array{null|int, null|int, null|string} $expected
     */
    #[TestDox('adapt should return [expireResolution, ttl, flag] for Predis SET command')]
    #[DataProvider('provideOptionsAndexpected')]
    public function testAdaptReturnsExpectedValues(array $options, array $expected): void
    {
        $adaptor = new SetAdaptor();

        $actual = $adaptor->adapt('SET', $options);

        $this->assertSame($expected, $actual);
    }
}
