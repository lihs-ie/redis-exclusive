<?php

namespace Lihs\RedisExclusive\Clients;

use Lihs\RedisExclusive\Clients\Option\OptionDispatcher;
use Predis\Client as Predis;

/**
 * Adaptor for Predis extension.
 */
class PredisClient implements RedisClient
{
    /**
     * Constructor.
     */
    public function __construct(private readonly Predis $redis, private readonly OptionDispatcher $dispatcher) {}

    /**
     * {@inheritDoc}
     */
    public function select(int $dbNumber): bool
    {
        $result = $this->redis->select($dbNumber);

        if (!\is_string($result)) {
            return false;
        }

        return 'OK' === $result;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, array $options = []): bool
    {
        $result = $this->redis->set(
            $key,
            $value,
            ...$this->dispatcher->dispatch('SET', $options)
        );

        return 'OK' === $result?->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): ?string
    {
        return $this->redis->get($key);
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
     *
     * @param array<string> $args
     */
    public function eval(string $luaScript, array $args = [], int $numKeys = 0): mixed
    {
        return $this->redis->eval($luaScript, $numKeys, ...$args);
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
        return $this->redis->exec() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function discard(): void
    {
        $this->redis->discard();
    }
}
