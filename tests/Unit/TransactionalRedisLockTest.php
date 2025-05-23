<?php

namespace Lihs\RedisExclusive;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Tests\Mock\Clients\RedisMock;
use Tests\Mock\RedisClientMock;

/**
 * @internal
 */
#[Group('unit')]
#[Group('transaction')]
#[CoversNothing]
final class TransactionalRedisLockTest extends TestCase
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

    #[TestDox('Instantiate the TransactionalRedisLock')]
    public function testInstantiateTransactionalRedisLock(): void
    {
        $transactionalRedisLock = new TransactionalRedisLock(
            new RedisClientMock(),
            new RedisLock(
                new RedisClientMock(),
                'lock:test',
                10,
                'test:owner'
            )
        );

        $this->assertInstanceOf(TransactionalRedisLock::class, $transactionalRedisLock);
    }

    #[TestDox('withTransaction should return the result of the callback in the transaction')]
    public function testWithTransactionReturnsCallbackResult(): void
    {
        $client = new RedisClientMock($this->redisMock);

        $transaction = new TransactionalRedisLock(
            $client,
            new RedisLock(
                $client,
                'lock:test',
                10,
                'test:owner'
            )
        );

        $transaction->withTransaction(function () use ($client): void {
            $client->set('key1', 'value1');
            $client->set('key2', 'value2');
            $client->set('key3', 'value3');
        });

        $value1 = $client->get('key1');
        $value2 = $client->get('key2');
        $value3 = $client->get('key3');

        $this->assertSame('value1', $value1);
        $this->assertSame('value2', $value2);
        $this->assertSame('value3', $value3);
    }

    #[TestDox('withTransaction should rollback when an exception is thrown')]
    public function testWithTransactionRollsBackOnException(): void
    {
        $this->markTestSkipped('This test requires a real Redis server. Use feature test instead.');
    }
}
