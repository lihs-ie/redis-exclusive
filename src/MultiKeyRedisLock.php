<?php

namespace Lihs\RedisExclusive;

use Lihs\RedisExclusive\Clients\RedisClient;

/**
 * Redis exclusive lock for multiple keys.
 */
final class MultiKeyRedisLock implements Lock
{
    /**
     * @var array<RedisLock>
     */
    private array $locks = [];

    private bool $locked = false;

    /**
     * Constructor.
     *
     * @param array<string> $keys
     */
    public function __construct(
        private readonly RedisClient $client,
        array $keys,
        private readonly int $ttlMillisecond,
        private readonly string $owner,
        private readonly string $prefix = 'lock:'
    ) {
        $sorted = $keys;

        // prevent deadlock
        \sort($sorted);

        foreach ($sorted as $key) {
            $this->locks[] = new RedisLock(
                $this->client,
                $this->prefix.$key,
                $this->ttlMillisecond,
                $this->owner
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function acquire(): bool
    {
        foreach ($this->locks as $lock) {
            if (!$lock->acquire()) {
                $this->release();

                return false;
            }
        }

        $this->locked = true;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function acquireWithRetry(int $maxWaitMs = 3000, int $backoffMs = 100): bool
    {
        $start = \microtime(true);

        while (true) {
            if ($this->acquire()) {
                return true;
            }

            $elapsed = (\microtime(true) - $start) * 1000;

            if ($elapsed >= $maxWaitMs) {
                return false;
            }

            // wait for the backoff time
            \usleep($backoffMs * 1000);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function release(): bool
    {
        foreach ($this->locks as $lock) {
            $lock->release();
        }

        $this->locked = false;

        return true;
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
