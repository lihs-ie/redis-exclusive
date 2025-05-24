<?php

namespace Tests\Unit;

use Lihs\RedisExclusive\MultiKeyRedisLock;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Tests\Mock\Clients\RedisMock;
use Tests\Mock\RedisClientMock;

/**
 * @internal
 */
#[TestDox('unit')]
#[CoversNothing]
final class MultiKeyRedisLockTest extends TestCase
{
    private RedisMock $redisMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redisMock = new RedisMock();
        $this->redisMock->reset();
    }

    #[TestDox('acquire should return true when all locks are acquired')]
    public function testAcquireReturnsTrueWhenAllLocksAcquired(): void
    {
        $lock = $this->createInstance();

        $this->assertTrue($lock->acquire());
    }

    #[TestDox('acquire should return false when any lock acquisition fails')]
    public function testAcquireReturnsFalseWhenAnyLockFails(): void
    {
        $this->redisMock->set('lock:unit:test2', 'another-owner');

        $lock = $this->createInstance(['unit:test1', 'unit:test2']);

        $this->assertFalse($lock->acquire());
        $this->assertFalse($lock->isLocked());
    }

    #[TestDox('acquireWithRetry should return true after the lock is released manually without ticks')]
    public function testAcquireWithRetryReturnsTrueAfterUnlock(): void
    {
        $redisMock = new RedisMock();
        $client = new RedisClientMock($redisMock);

        $keys = ['lock1', 'lock2'];
        $ttl = 1000;
        $owner = 'test-owner';
        $prefix = 'lock:';

        $redisMock->set($prefix.'lock2', 'other-owner');

        $lock = new MultiKeyRedisLock($client, $keys, $ttl, $owner, $prefix);

        $start = \microtime(true);
        $result = false;

        while ((\microtime(true) - $start) * 1000 < 1000) {
            $elapsedMs = (\microtime(true) - $start) * 1000;

            if ($elapsedMs >= 300 && 'other-owner' === $redisMock->get($prefix.'lock2')) {
                $redisMock->del($prefix.'lock2');
            }

            $result = $lock->acquire();

            if ($result) {
                break;
            }

            \usleep(100 * 1000);
        }

        $this->assertTrue($result);
        $this->assertTrue($lock->isLocked());
    }

    #[TestDox('release should return true when all locks are released')]
    public function testReleaseReturnsTrueWhenLocksAreReleased(): void
    {
        $lock = $this->createInstance();
        $lock->acquire();

        $this->assertTrue($lock->release());
    }

    #[TestDox('release should be safe to call even if no locks are held')]
    public function testReleaseIsIdempotent(): void
    {
        $lock = $this->createInstance();

        $this->assertTrue($lock->release());
    }

    #[TestDox('isLocked should return true after successful acquire')]
    public function testIsLockedReturnsTrueAfterAcquire(): void
    {
        $lock = $this->createInstance();
        $lock->acquire();

        $this->assertTrue($lock->isLocked());
    }

    #[TestDox('isLocked should return false before any acquire')]
    public function testIsLockedReturnsFalseBeforeAcquire(): void
    {
        $lock = $this->createInstance();

        $this->assertFalse($lock->isLocked());
    }

    #[TestDox('acquireWith should return the result of the callback')]
    public function testAcquireWithReturnsCallbackResult(): void
    {
        $lock = $this->createInstance();

        $expected = \mt_rand(1, 100);

        $result = $lock->acquireWith(fn () => $expected);

        $this->assertSame($expected, $result);
    }

    #[TestDox('acquireWith should return null if acquire fails')]
    public function testAcquireWithReturnsNullOnFailedAcquire(): void
    {
        $this->redisMock->set('lock:unit:test2', 'other');
        $lock = $this->createInstance(['unit:test1', 'unit:test2']);

        $result = $lock->acquireWith(fn () => 123);

        $this->assertNull($result);
    }

    /**
     * Create a MultiKeyRedisLock instance.
     *
     * @param array<string> $keys
     */
    private function createInstance(array $keys = ['unit:test1', 'unit:test2']): MultiKeyRedisLock
    {
        return new MultiKeyRedisLock(
            new RedisClientMock($this->redisMock),
            $keys,
            3000,
            'test-owner'
        );
    }
}
