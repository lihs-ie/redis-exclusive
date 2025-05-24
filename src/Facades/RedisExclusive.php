<?php

namespace Lihs\RedisExclusive\Facades;

use Illuminate\Support\Facades\Facade;
use Lihs\RedisExclusive\LockManager;

/**
 * @method static \Lihs\RedisExclusive\RedisLock              lock(string $key, int $ttl = 10000, ?string $owner = null)
 * @method static \Lihs\RedisExclusive\TransactionalRedisLock transactional(string $key, int $ttl = 10000, ?string $owner = null)
 * @method static \Lihs\RedisExclusive\MultiKeyRedisLock      multiLock(array<string> $keys, int $ttl = 10000, ?string $owner = null)
 * @method static mixed                                       multiTransactional(array<string> $keys, int $ttl = 10000, ?string $owner = null)
 *
 * @see LockManager
 */
class RedisExclusive extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return LockManager::class;
    }
}
