<?php

namespace Lihs\RedisExclusive;

use Lihs\RedisExclusive\Clients\RedisClient;
use Lihs\RedisExclusive\Exceptions\LockAcquisitionException;

/**
 * Transactional Redis lock.
 */
final class TransactionalRedisLock
{
    /**
     * Constructor.
     */
    public function __construct(
        private readonly RedisClient $client,
        private readonly Lock $lock,
    ) {}

    /**
     * Execute a transaction with the lock.
     *
     * @template R
     *
     * @param \Closure(): R $callback
     *
     * @return R
     */
    public function withTransaction(\Closure $callback): mixed
    {
        if (!$this->lock->acquire()) {
            throw new LockAcquisitionException();
        }

        $this->client->multi();

        try {
            $result = $callback();

            $this->client->exec();

            return $result;
        } catch (\Throwable $exception) {
            $this->client->discard();

            throw $exception;
        } finally {
            $this->lock->release();
        }
    }
}
