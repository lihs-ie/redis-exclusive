<?php

namespace Lihs\RedisExclusive\Clients;

use Lihs\RedisExclusive\Clients\Option\OptionDispatcher;

/**
 * Adaptor for PhpRedis extension.
 */
final class PhpRedisClient implements RedisClient
{
    /**
     * Constructor.
     */
    public function __construct(private readonly \Redis $redis, private readonly OptionDispatcher $dispatcher) {}

    /**
     * {@inheritDoc}
     */
    public function select(int $dbNumber): bool
    {
        return $this->redis->select($dbNumber);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, array $options = []): bool
    {
        return $this->redis->set(
            $key,
            $value,
            $this->dispatcher->dispatch('SET', $options)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): ?string
    {
        $value = $this->redis->get($key);

        if (\is_null($value) || !\is_string($value)) {
            return null;
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string ...$keys): int
    {
        return $this->redis->del(...$keys);
    }

    /**
     * {@inheritDoc}
     */
    public function eval(string $luaScript, array $args = [], int $numKeys = 0): mixed
    {
        return $this->redis->eval($luaScript, $args, $numKeys);
    }
}
