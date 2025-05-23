<?php

namespace Lihs\RedisExclusive;

use Lihs\RedisExclusive\Clients\RedisClient;

/**
 * Managing Redis locks.
 */
final class LockManager
{
    /**
     * constructor.
     *
     * @param int<0, 15> $dbNumber
     */
    public function __construct(
        private readonly RedisClient $client,
        private readonly string $prefix = 'lock:',
        private readonly int $dbNumber = 0
    ) {}

    /**
     * Create a new lock.
     */
    public function lock(string $key, int $ttlMillisecond, string $owner): Lock
    {
        if (0 < $this->dbNumber) {
            $this->client->select($this->dbNumber);
        }

        return new RedisLock(
            $this->client,
            $this->prefix.$key,
            $ttlMillisecond,
            $owner
        );
    }

    /**
     * Create a new lock for multiple keys.
     *
     * @param array<string> $keys
     */
    public function multiLock(array $keys, int $ttlMillisecond, string $owner): Lock
    {
        if (0 < $this->dbNumber) {
            $this->client->select($this->dbNumber);
        }

        return new MultiKeyRedisLock(
            $this->client,
            $keys,
            $ttlMillisecond,
            $owner,
            $this->prefix
        );
    }

    /**
     * Execute a callback within a transactional Redis block.
     *
     * @template R
     *
     * @param \Closure(): R $callback
     *
     * @return R
     */
    public function transactional(
        string $key,
        \Closure $callback,
        string $owner = 'default',
        int $ttlMillisecond = 3000,
    ): mixed {
        if (0 !== $this->dbNumber) {
            $this->client->select($this->dbNumber);
        }

        $lock = new RedisLock(
            $this->client,
            $this->prefix.$key,
            $ttlMillisecond,
            $owner
        );

        $transaction = new TransactionalRedisLock($this->client, $lock);

        return $transaction->withTransaction($callback);
    }

    /**
     * Execute a callback within a transactional Redis block with multiple locks.
     *
     * @template R
     *
     * @param \Closure(): R $callback
     * @param array<string> $keys
     */
    public function multiTransactional(
        array $keys,
        \Closure $callback,
        string $owner = 'default',
        int $ttlMillisecond = 3000,
    ): mixed {
        if (0 !== $this->dbNumber) {
            $this->client->select($this->dbNumber);
        }

        $lock = new MultiKeyRedisLock(
            $this->client,
            $keys,
            $ttlMillisecond,
            $owner,
            $this->prefix
        );

        $transaction = new TransactionalRedisLock($this->client, $lock);

        return $transaction->withTransaction($callback);
    }

    /**
     * Create a new LockManager with a different database.
     *
     * @param int<0, 15> $dbNumber
     */
    public function switchDatabase(int $dbNumber): self
    {
        return new self(
            $this->client,
            $this->prefix,
            $dbNumber
        );
    }

    /**
     * Get the current database number.
     */
    public function databaseNumber(): int
    {
        return $this->dbNumber;
    }
}
