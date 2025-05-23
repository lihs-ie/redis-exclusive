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
        $result = $this->redis->set(
            $key,
            $value,
            $this->dispatcher->dispatch('SET', $options)
        );

        if (\is_string($result)) {
            return 'OK' === $result;
        }

        if ($result instanceof \Redis) {
            return true;
        }

        return $result;
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

    /**
     * {@inheritDoc}
     */
    public function multi(): void
    {
        $this->redis->multi();
    }

    /**
     * {@inheritDoc}
     */
    public function exec(): array
    {
        return $this->redis->exec();
    }

    /**
     * {@inheritDoc}
     */
    public function discard(): void
    {
        $this->redis->discard();
    }
}
