<?php

namespace Lihs\RedisExclusive;

use Lihs\RedisExclusive\Clients\RedisClient;

/**
 * Redis exclusive lock implementation.
 */
final class RedisLock implements Lock
{
    private bool $locked = false;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly RedisClient $client,
        private readonly string $key,
        private readonly int $ttlMillisecond,
        private readonly string $owner
    ) {}

    /**
     * {@inheritDoc}
     */
    public function acquire(): bool
    {
        $this->locked = $this->client->set(
            $this->key,
            $this->owner,
            ['PX' => $this->ttlMillisecond, 'NX' => true]
        );

        return $this->locked;
    }

    /**
     * {@inheritDoc}
     */
    public function release(): bool
    {
        if (!$this->locked) {
            return false;
        }

        $luaScript = <<<'LUA'
      if redis.call('get', KEYS[1]) == ARGV[1] then
        return redis.call('del', KEYS[1])
      else
        return 0
      end
    LUA;

        $result = $this->client->eval($luaScript, [$this->key, $this->owner], 1);

        $this->locked = false;

        return 1 === $result;
    }

    /**
     * {@inheritDoc}
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * {@inheritDoc}
     */
    public function acquireWith(\Closure $callback): mixed
    {
        if (!$this->acquire()) {
            return null;
        }

        try {
            return $callback();
        } finally {
            $this->release();
        }
    }
}
