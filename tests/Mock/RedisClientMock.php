<?php

namespace Tests\Mock;

use Lihs\RedisExclusive\Clients\Option\OptionDispatcher;
use Lihs\RedisExclusive\Clients\RedisClient;
use Tests\Mock\Clients\Option\SetAdaptor;
use Tests\Mock\Clients\RedisMock;

/**
 * Mock Redis client for testing.
 */
class RedisClientMock implements RedisClient
{
    private readonly OptionDispatcher $dispatcher;

    /**
     * Constructor.
     */
    public function __construct(private readonly RedisMock $redis = new RedisMock())
    {
        $this->dispatcher = new OptionDispatcher(new SetAdaptor());

        $this->redis->registerEval(
            <<<'LUA'
      if redis.call('get', KEYS[1]) == ARGV[1] then
        return redis.call('del', KEYS[1])
      else
        return 0
      end
    LUA,
            function (array $keys, array $args): int {
                $key = $keys[0];
                $expectedValue = $args[0];
                $actualValue = $this->redis->get($key);

                if ($actualValue === $expectedValue) {
                    $this->redis->del($key);

                    return 1;
                }

                return 0;
            }
        );
    }

    /**
     * Reset the mock client.
     */
    public function reset(): void
    {
        $this->redis->reset();
    }

    /**
     * {@inheritDoc}
     */
    public function select(int $dbNumber): bool
    {
        $this->redis->selectStorage((string) $dbNumber);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, array $options = []): bool
    {
        $result = $this->redis
            ->set(
                $key,
                $value,
                $this->dispatcher->dispatch('SET', $options)
            )
        ;

        return 'OK' === $result || true === $result || 1 === $result;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): ?string
    {
        $result = $this->redis->get($key);

        if (\is_string($result) || \is_null($result)) {
            return $result;
        }

        throw new \RuntimeException('Unexpected value type: '.\gettype($result));
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string ...$keys): int
    {
        $result = $this->redis->del(...$keys);

        return \is_int($result) ? $result : 0;
    }

    /**
     * {@inheritDoc}
     */
    public function eval(string $luaScript, array $args = [], int $numKeys = 0): mixed
    {
        return $this->redis->eval($luaScript, $numKeys, ...$args);
    }
}
