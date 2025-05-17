<?php

namespace Tests\Unit;

use Lihs\RedisExclusive\Lock;
use Lihs\RedisExclusive\LockManager;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Tests\Mock\RedisClientMock;

/**
 * @internal
 */
#[Group('unit')]
#[CoversNothing]
final class LockManagerTest extends TestCase
{
    #[TestDox('Instantiate the LockManager')]
    public function testInstantiateLockManager(): void
    {
        $lockManager = new LockManager(
            new RedisClientMock(),
            'lock:test',
            \mt_rand(0, 15)
        );

        $this->assertInstanceOf(LockManager::class, $lockManager);
    }

    #[TestDox('lock should return a Lock instance')]
    public function testLockReturnsLockInstance(): void
    {
        $lockManager = new LockManager(
            new RedisClientMock(),
            'lock:test',
            \mt_rand(0, 15)
        );

        $lock = $lockManager->lock('test:key', 10, 'test:owner');

        $this->assertInstanceOf(Lock::class, $lock);
    }

    #[TestDox('switchDatabase should return LockManager with updated database')]
    public function testSwitchDatabaseReturnsLockManagerWithUpdatedDatabase(): void
    {
        $before = \mt_rand(0, 14);

        $beforeManager = new LockManager(
            new RedisClientMock(),
            'lock:test',
            $before
        );

        $after = $before + 1;

        $actual = $beforeManager->switchDatabase($after);

        $this->assertInstanceOf(LockManager::class, $actual);
        $this->assertNotSame($beforeManager, $actual);
        $this->assertSame($after, $actual->databaseNumber());
    }

    #[TestDox('databaseNumber should return the current database number')]
    public function testDatabaseNumberReturnsCurrentDatabaseNumber(): void
    {
        $dbNumber = \mt_rand(0, 15);

        $lockManager = new LockManager(
            new RedisClientMock(),
            'lock:test',
            $dbNumber
        );

        $this->assertSame($dbNumber, $lockManager->databaseNumber());
    }
}
