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
    public function lock(string $key, int $ttl, string $owner): Lock
    {
        if (0 < $this->dbNumber) {
            $this->client->select($this->dbNumber);
        }

        return new RedisLock(
            $this->client,
            $this->prefix.$key,
            $ttl,
            $owner
        );
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
