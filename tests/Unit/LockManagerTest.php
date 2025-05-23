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
#[Group('transaction')]
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

    #[TestDox('transactional should return the result of the callback in the transaction')]
    public function testTransactionalReturnsCallbackResult(): void
    {
        $client = new RedisClientMock();

        $lockManager = new LockManager(
            $client,
            'lock:test',
            \mt_rand(0, 15)
        );

        $expected = \mt_rand(0, 255);

        $result = $lockManager->transactional(
            'test:key',
            function () use ($expected): int {
                return $expected;
            },
            'test:owner',
            10,
        );

        $this->assertSame($expected, $result);
    }

    #[TestDox('transactional should return the result of the callback in the transaction with a different database')]
    public function testTransactionalReturnsCallbackResultWithDifferentDatabase(): void
    {
        $client = new RedisClientMock();

        $lockManager = new LockManager(
            $client,
            'lock:test',
            \mt_rand(0, 15)
        );

        $expected = \mt_rand(0, 255);

        $result = $lockManager->transactional(
            'test:key',
            function () use ($expected): int {
                return $expected;
            },
            'test:owner',
            10,
        );

        $this->assertSame($expected, $result);
    }

    #[TestDox('multiTransactional should return the result of the callback in the transaction')]
    public function testMultiTransactionalReturnsCallbackResult(): void
    {
        $client = new RedisClientMock();

        $lockManager = new LockManager(
            $client,
            'lock:test',
            \mt_rand(0, 15)
        );

        $expected = \mt_rand(1, 1000);

        $result = $lockManager->multiTransactional(
            ['test:key:1', 'test:key:2'],
            fn () => $expected,
            'test:owner',
            3000
        );

        $this->assertSame($expected, $result);
    }

    #[TestDox('multiTransactional should return the result of the callback in the transaction with a different database')]
    public function testMultiTransactionalReturnsCallbackResultWithDifferentDatabase(): void
    {
        $client = new RedisClientMock();

        $lockManager = new LockManager(
            $client,
            'lock:test',
            \mt_rand(0, 14)
        );

        $newDatabase = 15;
        $switched = $lockManager->switchDatabase($newDatabase);

        $expected = \uniqid('result_', true);

        $result = $switched->multiTransactional(
            ['test:key:3', 'test:key:4'],
            fn () => $expected,
            'owner:multi',
            3000
        );

        $this->assertSame($expected, $result);
        $this->assertSame($newDatabase, $switched->databaseNumber());
    }
}
