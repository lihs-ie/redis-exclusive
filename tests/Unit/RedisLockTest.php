<?php

namespace Tests\Unit;

use Lihs\RedisExclusive\RedisLock;
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
final class RedisLockTest extends TestCase
{
    private RedisMock $redisMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->redisMock = new RedisMock();
        $this->redisMock->reset();
    }

    #[TestDox('acquire should return true when lock is acquired')]
    public function testAcquireReturnsTrueWhenLockIsAcquired(): void
    {
        $lock = $this->createInstance();

        $this->assertTrue($lock->acquire());
    }

    #[TestDox('acquire should return false when lock is already acquired')]
    public function testAcquireReturnsFalseWhenLockIsAlreadyAcquired(): void
    {
        $lock = $this->createInstance();
        $lock->acquire();

        $this->assertFalse($lock->acquire());
    }

    #[TestDox('acquire should return false when lock is not acquired')]
    public function testReleaseReturnsTrueWhenLockIsReleased(): void
    {
        $lock = $this->createInstance();
        $lock->acquire();

        $this->assertTrue($lock->release());
    }

    #[TestDox('acquireWithRetry should return true after the lock is released manually without ticks')]
    public function testAcquireWithRetryReturnsTrueAfterUnlock(): void
    {
        $redisMock = new RedisMock();
        $client = new RedisClientMock($redisMock);

        $lock = new RedisLock($client, 'lock:test', 1000, 'test-owner');

        $start = \microtime(true);

        $result = false;

        while ((\microtime(true) - $start) * 1000 < 1000) {
            if ((\microtime(true) - $start) * 1000 > 200) {
                $redisMock->del('lock:test');
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

    #[TestDox('release should return false when lock is not acquired')]
    public function testReleaseReturnsFalseWhenLockIsNotAcquired(): void
    {
        $lock = $this->createInstance();

        $this->assertFalse($lock->release());
    }

    #[TestDox('isLocked should return true when lock is acquired')]
    public function testIsLockedReturnsTrueWhenLockIsAcquired(): void
    {
        $lock = $this->createInstance();
        $lock->acquire();

        $this->assertTrue($lock->isLocked());
    }

    #[TestDox('isLocked should return false when lock is not acquired')]
    public function testIsLockedReturnsFalseWhenLockIsNotAcquired(): void
    {
        $lock = $this->createInstance();

        $this->assertFalse($lock->isLocked());
    }

    #[TestDox('acquireWith should return result of callback when lock is acquired')]
    public function testAcquireWithReturnsResultOfCallbackWhenLockIsAcquired(): void
    {
        $lock = $this->createInstance();

        $expected = \mt_rand(0, 255);

        $result = $lock->acquireWith(function () use ($expected): int {
            return $expected;
        });

        $this->assertSame($expected, $result);
    }

    #[TestDox('acquireWith should return null when lock is not acquired')]
    public function testAcquireWithReturnsNullWhenLockIsNotAcquired(): void
    {
        $lock = $this->createInstance();

        $lock->acquire();

        $result = $lock->acquireWith(function (): int {
            return \mt_rand(0, 255);
        });

        $this->assertNull($result);
    }

    /**
     * Creating a lock instance.
     */
    private function createInstance(): RedisLock
    {
        return new RedisLock(
            new RedisClientMock($this->redisMock),
            'lock:test',
            10,
            'test',
        );
    }
}
