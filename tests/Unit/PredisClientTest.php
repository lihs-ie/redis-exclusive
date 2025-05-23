<?php

namespace Tests\Unit;

use Lihs\RedisExclusive\Clients\Option\OptionAdaptor;
use Lihs\RedisExclusive\Clients\Option\OptionDispatcher;
use Lihs\RedisExclusive\Clients\PredisClient;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Predis\Client as Predis;
use Predis\Response\Status;

/**
 * @internal
 */
#[Group('unit')]
#[CoversNothing]
final class PredisClientTest extends TestCase
{
    #[TestDox('Instantiate the PredisClient')]
    public function testInstantiatePhpRedisClient(): void
    {
        $redisMock = $this->createMock(Predis::class);

        $redisClient = new PredisClient(
            $redisMock,
            new OptionDispatcher()
        );

        $this->assertInstanceOf(PredisClient::class, $redisClient);
    }

    #[TestDox('select returns true when database is selected')]
    public function testSelectReturnsTrueWhenDatabaseIsSelected(): void
    {
        $dbNumber = \mt_rand(0, 15);

        $redisMock = $this->getMockBuilder(Predis::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock()
        ;

        $redisMock->expects($this->once())
            ->method('__call')
            ->with('select', [$dbNumber])
            ->willReturn('OK')
        ;

        $redisClient = new PredisClient(
            $redisMock,
            new OptionDispatcher()
        );

        $actual = $redisClient->select($dbNumber);

        $this->assertTrue($actual);
    }

    #[TestDox('select returns false when database selection fails')]
    public function testSelectReturnsFalseWhenDatabaseSelectionFails(): void
    {
        $dbNumber = \mt_rand(0, 15);

        $redisMock = $this->getMockBuilder(Predis::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock()
        ;

        $redisMock->expects($this->once())
            ->method('__call')
            ->with('select', [$dbNumber])
            ->willReturn(false)
        ;

        $redisClient = new PredisClient(
            $redisMock,
            new OptionDispatcher()
        );

        $actual = $redisClient->select($dbNumber);

        $this->assertFalse($actual);
    }

    #[TestDox('set returns true when value is set')]
    public function testSetReturnsTrueWhenValueIsSet(): void
    {
        $key = (string) \mt_rand(0, 255);
        $value = (string) \mt_rand(256, 511);
        $options = ['EX' => 10];

        $redisMock = $this->getMockBuilder(Predis::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock()
        ;

        $redisMock->expects($this->once())
            ->method('__call')
            ->with('set', [$key, $value, $options])
            ->willReturn(new Status('OK'))
        ;

        $adaptor = $this->createOptionAdaptor('SET');

        $optionDispatcher = $this->createOptionDispatcher($adaptor);

        $redisClient = new PredisClient(
            $redisMock,
            $optionDispatcher
        );

        $actual = $redisClient->set($key, $value, $options);

        $this->assertTrue($actual);
    }

    #[TestDox('set returns false when failed to set value')]
    public function testSetReturnsFalseWhenFailedToSetValue(): void
    {
        $key = (string) \mt_rand(0, 255);
        $value = (string) \mt_rand(256, 511);
        $options = ['EX' => 10];

        $redisMock = $this->getMockBuilder(Predis::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock()
        ;

        $redisMock->expects($this->once())
            ->method('__call')
            ->with('set', [$key, $value, $options])
            ->willReturn(new Status('ERR'))
        ;

        $adaptor = $this->createOptionAdaptor('SET');

        $optionDispatcher = $this->createOptionDispatcher($adaptor);

        $redisClient = new PredisClient(
            $redisMock,
            $optionDispatcher
        );

        $actual = $redisClient->set($key, $value, $options);

        $this->assertFalse($actual);
    }

    #[TestDox('get returns the value when key exists')]
    public function testGetReturnsTheValueWhenKeyExists(): void
    {
        $key = (string) \mt_rand(0, 255);
        $value = (string) \mt_rand(256, 511);

        $redisMock = $this->getMockBuilder(Predis::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock()
        ;

        $redisMock->expects($this->once())
            ->method('__call')
            ->with('get', [$key])
            ->willReturn($value)
        ;

        $redisClient = new PredisClient(
            $redisMock,
            $this->createOptionDispatcher()
        );

        $actual = $redisClient->get($key);

        $this->assertSame($value, $actual);
    }

    #[TestDox('get returns null when key does not exist')]
    public function testGetReturnsNullWhenKeyDoesNotExist(): void
    {
        $key = (string) \mt_rand(0, 255);

        $redisMock = $this->getMockBuilder(Predis::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock()
        ;

        $redisMock->expects($this->once())
            ->method('__call')
            ->with('get', [$key])
            ->willReturn(null)
        ;

        $redisClient = new PredisClient(
            $redisMock,
            $this->createOptionDispatcher()
        );

        $actual = $redisClient->get($key);

        $this->assertNull($actual);
    }

    #[TestDox('remove returns the number of keys removed')]
    public function testRemoveReturnsTheNumberOfKeysRemoved(): void
    {
        $count = \mt_rand(2, 10);
        $keys = \array_map(
            fn (int $index): string => (string) $index,
            \range(1, $count)
        );

        $redisMock = $this->getMockBuilder(Predis::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock()
        ;

        $redisMock->expects($this->once())
            ->method('__call')
            ->with('del', $keys)
            ->willReturn($count)
        ;

        $redisClient = new PredisClient(
            $redisMock,
            $this->createOptionDispatcher()
        );

        $actual = $redisClient->remove(...$keys);

        $this->assertSame($count, $actual);
    }

    #[TestDox('eval returns the result of the Lua script')]
    public function testEvalReturnsTheResultOfTheLuaScript(): void
    {
        $luaScript = 'return redis.call("PING")';
        $args = ['foo', 'bar'];
        $numKeys = 2;

        $redisMock = $this->getMockBuilder(Predis::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock()
        ;

        $redisMock->expects($this->once())
            ->method('__call')
            ->with('eval', [$luaScript, $numKeys, ...$args])
            ->willReturn('PONG')
        ;

        $redisClient = new PredisClient(
            $redisMock,
            $this->createOptionDispatcher()
        );

        $actual = $redisClient->eval($luaScript, $args, $numKeys);

        $this->assertSame('PONG', $actual);
    }

    /**
     * Creates a PhpRedis mock.
     */
    private function createOptionDispatcher(OptionAdaptor ...$adaptors): OptionDispatcher
    {
        return new OptionDispatcher(...$adaptors);
    }

    /**
     * Creates an OptionAdaptor for the given command.
     */
    private function createOptionAdaptor(string $command): OptionAdaptor
    {
        return new class($command) implements OptionAdaptor {
            public function __construct(private readonly string $targetCommand) {}

            /**
             * {@inheritDoc}
             */
            public function support(string $command): bool
            {
                return $this->targetCommand === $command;
            }

            /**
             * {@inheritDoc}
             *
             * @param array<string> $options
             */
            public function adapt(string $command, array $options): array
            {
                return $options;
            }
        };
    }
}
