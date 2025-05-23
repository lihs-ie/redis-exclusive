<?php

namespace Tests\Unit;

use Lihs\RedisExclusive\Clients\Option\OptionAdaptor;
use Lihs\RedisExclusive\Clients\Option\OptionDispatcher;
use Lihs\RedisExclusive\Clients\PhpRedisClient;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
#[Group('unit')]
final class PhpRedisClientTest extends TestCase
{
    #[TestDox('Instantiate the PhpRedisClient')]
    public function testInstantiatePhpRedisClient(): void
    {
        $redisMock = $this->createMock(\Redis::class);

        $redisClient = new PhpRedisClient(
            $redisMock,
            new OptionDispatcher()
        );

        $this->assertInstanceOf(PhpRedisClient::class, $redisClient);
    }

    #[TestDox('select returns true when database is selected')]
    public function testSelectReturnsTrueWhenDatabaseIsSelected(): void
    {
        $dbNumber = \mt_rand(0, 15);

        $redisMock = $this->createMock(\Redis::class);
        $redisMock
            ->expects($this->once())
            ->method('select')
            ->with($dbNumber)
            ->willReturn(true)
        ;

        $redisClient = new PhpRedisClient(
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

        $redisMock = $this->createMock(\Redis::class);
        $redisMock
            ->expects($this->once())
            ->method('select')
            ->with($dbNumber)
            ->willReturn(false)
        ;

        $redisClient = new PhpRedisClient(
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

        $redisMock = $this->createMock(\Redis::class);
        $redisMock
            ->expects($this->once())
            ->method('set')
            ->with($key, $value, $options)
            ->willReturn(true)
        ;

        $adaptor = $this->createOptionAdaptor('SET');

        $optionDispatcher = $this->createOptionDispatcher($adaptor);

        $redisClient = new PhpRedisClient(
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

        $redisMock = $this->createMock(\Redis::class);
        $redisMock
            ->expects($this->once())
            ->method('set')
            ->with($key, $value, $options)
            ->willReturn(false)
        ;

        $adaptor = $this->createOptionAdaptor('SET');

        $optionDispatcher = $this->createOptionDispatcher($adaptor);

        $redisClient = new PhpRedisClient(
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

        $redisMock = $this->createMock(\Redis::class);
        $redisMock
            ->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($value)
        ;

        $redisClient = new PhpRedisClient(
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

        $redisMock = $this->createMock(\Redis::class);
        $redisMock
            ->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn(null)
        ;

        $redisClient = new PhpRedisClient(
            $redisMock,
            $this->createOptionDispatcher()
        );

        $actual = $redisClient->get($key);

        $this->assertNull($actual);
    }

    #[TestDox('get returns null when value is not a string')]
    public function testGetReturnsNullWhenValueIsNotAString(): void
    {
        $key = (string) \mt_rand(0, 255);
        $value = ['foo' => 'bar'];

        $redisMock = $this->createMock(\Redis::class);
        $redisMock
            ->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($value)
        ;

        $redisClient = new PhpRedisClient(
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

        $redisMock = $this->createMock(\Redis::class);
        $redisMock
            ->expects($this->once())
            ->method('del')
            ->with(...$keys)
            ->willReturn($count)
        ;

        $redisClient = new PhpRedisClient(
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

        $redisMock = $this->createMock(\Redis::class);
        $redisMock
            ->expects($this->once())
            ->method('eval')
            ->with($luaScript, $args, $numKeys)
            ->willReturn('PONG')
        ;

        $redisClient = new PhpRedisClient(
            $redisMock,
            $this->createOptionDispatcher()
        );

        $actual = $redisClient->eval($luaScript, $args, $numKeys);

        $this->assertSame('PONG', $actual);
    }

    #[TestDox('multi starts a transaction')]
    #[DoesNotPerformAssertions]
    public function testMultiStartsATransaction(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $redisMock
            ->expects($this->once())
            ->method('multi')
        ;

        $redisClient = new PhpRedisClient(
            $redisMock,
            $this->createOptionDispatcher()
        );

        $redisClient->multi();
    }

    #[TestDox('exec returns the result of the transaction')]
    public function testExecReturnsTheResultOfTheTransaction(): void
    {
        $result = ['OK', 'PONG'];

        $redisMock = $this->createMock(\Redis::class);
        $redisMock
            ->expects($this->once())
            ->method('exec')
            ->willReturn($result)
        ;

        $redisClient = new PhpRedisClient(
            $redisMock,
            $this->createOptionDispatcher()
        );

        $actual = $redisClient->exec();

        $this->assertSame($result, $actual);
    }

    #[TestDox('discard discards the transaction')]
    #[DoesNotPerformAssertions]
    public function testDiscardDiscardsTheTransaction(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $redisMock
            ->expects($this->once())
            ->method('discard')
        ;

        $redisClient = new PhpRedisClient(
            $redisMock,
            $this->createOptionDispatcher()
        );

        $redisClient->discard();
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
