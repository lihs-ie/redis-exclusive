<?php

namespace Tests\Unit\Option\PhpRedis;

use Lihs\RedisExclusive\Clients\Option\PhpRedis\SetAdaptor;
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
#[Group('phpredis')]
#[CoversNothing]
final class SetAdaptorTest extends TestCase
{
    /**
     * Provides options and their adapted values for the SET command.
     */
    public static function provideOptionsAndAdapted(): \Generator
    {
        yield 'EX' => [
            'options' => ['EX' => 10],
            'adapted' => ['EX' => 10],
        ];

        yield 'PX' => [
            'options' => ['PX' => 10],
            'adapted' => ['PX' => 10],
        ];

        yield 'NX is true' => [
            'options' => ['NX' => true],
            'adapted' => ['NX'],
        ];

        yield 'NX is false' => [
            'options' => ['NX' => false],
            'adapted' => [],
        ];

        yield 'XX is true' => [
            'options' => ['XX' => true],
            'adapted' => ['XX'],
        ];

        yield 'XX is false' => [
            'options' => ['XX' => false],
            'adapted' => [],
        ];

        yield 'EXAT' => [
            'options' => ['EXAT' => 10],
            'adapted' => ['EXAT' => 10],
        ];

        yield 'PXAT' => [
            'options' => ['PXAT' => 10],
            'adapted' => ['PXAT' => 10],
        ];

        yield 'KEEPTTL is true' => [
            'options' => ['KEEPTTL' => true],
            'adapted' => ['KEEPTTL'],
        ];

        yield 'KEEPTTL is false' => [
            'options' => ['KEEPTTL' => false],
            'adapted' => [],
        ];

        yield 'GET is true' => [
            'options' => ['GET' => true],
            'adapted' => ['GET'],
        ];

        yield 'GET is false' => [
            'options' => ['GET' => false],
            'adapted' => [],
        ];

        yield 'all' => [
            'options' => [
                'EX' => 10,
                'PX' => 20,
                'NX' => true,
                'XX' => false,
                'EXAT' => 30,
                'PXAT' => 40,
                'KEEPTTL' => true,
                'GET' => false,
            ],
            'adapted' => [
                'EX' => 10,
                'PX' => 20,
                'NX',
                'EXAT' => 30,
                'PXAT' => 40,
                'KEEPTTL',
            ],
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
     * @param T $options
     * @param T $adapted
     */
    #[TestDox('adapt should return adapted options for PhpRedis SET command')]
    #[DataProvider('provideOptionsAndAdapted')]
    public function testAdaptReturnsAdaptedOptions(array $options, array $adapted): void
    {
        $adaptor = new SetAdaptor();

        $actual = $adaptor->adapt('SET', $options);

        $this->assertSame($adapted, $actual);
    }
}
