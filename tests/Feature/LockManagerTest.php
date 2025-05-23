<?php

namespace Tests\Feature;

use Lihs\RedisExclusive\Clients\Option\OptionDispatcher;
use Lihs\RedisExclusive\Clients\Option\PhpRedis\SetAdaptor as PhpRedisSetAdaptor;
use Lihs\RedisExclusive\Clients\Option\Predis\SetAdaptor as PredisSetAdaptor;
use Lihs\RedisExclusive\Clients\PhpRedisClient;
use Lihs\RedisExclusive\Clients\PredisClient;
use Lihs\RedisExclusive\Clients\RedisClient;
use Lihs\RedisExclusive\LockManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Predis\Client as Predis;
use Redis as PhpRedis;
use Symfony\Component\Process\Process;
use Tests\Helpers\UsesDockerRedis;

/**
 * @internal
 * @coversNothing
 */
#[Group('feature')]
#[Group('transaction')]
final class LockManagerTest extends TestCase
{
    use UsesDockerRedis;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $redis = $this->createPhpRedisConnection();

        $redis->flushAll();
    }

    /**
     * Provide clients for testing.
     */
    public static function provideClient(): \Generator
    {
        yield 'PhpRedis' => [new PhpRedisClient(
            self::createPhpRedisConnection(),
            new OptionDispatcher(new PhpRedisSetAdaptor())
        )];

        yield 'Predis' => [new PredisClient(
            self::createPredisConnection(),
            new OptionDispatcher(new PredisSetAdaptor())
        )];
    }

    #[TestDox('testTransaction should succeed (requires Docker Redis)')]
    #[DataProvider('provideClient')]
    public function testTransactionSuccess(RedisClient $client): void
    {
        $manager = new LockManager(
            $client,
            'feature:test',
            0
        );

        $client->set('key1', 'value1');
        $client->set('key2', 'value2');
        $client->set('key3', 'value3');

        $manager->transactional('test:transaction', function () use ($client): void {
            $client->set('key1', 'value10');
            $client->set('key2', 'value20');
            $client->set('key3', 'value30');
        });

        $value1 = $client->get('key1');
        $value2 = $client->get('key2');
        $value3 = $client->get('key3');

        $this->assertSame('value10', $value1);
        $this->assertSame('value20', $value2);
        $this->assertSame('value30', $value3);
    }

    #[TestDox('Transaction rollback should succeed (requires Docker Redis)')]
    #[DataProvider('provideClient')]
    public function testTransactionSuccessRollbackWithException(RedisClient $client): void
    {
        $manager = new LockManager(
            $client,
            'feature:test',
            0
        );

        $client->set('key1', 'value1');
        $client->set('key2', 'value2');
        $client->set('key3', 'value3');

        try {
            $manager->transactional('test:transaction', function () use ($client): void {
                $client->set('key1', 'value10');
                $client->set('key2', 'value20');
                $client->set('key3', 'value30');

                throw new \Exception();
            });
        } catch (\Throwable) {
        }

        $this->assertSame('value1', $client->get('key1'));
        $this->assertSame('value2', $client->get('key2'));
        $this->assertSame('value3', $client->get('key3'));
    }

    #[TestDox('exclusive lock between two processes (requires Docker Redis)')]
    public function testExclusiveLockBetweenTwoProcessesWithSameKey(): void
    {
        $workerPath = __DIR__.'/../Fixtures/lock_worker.php';

        $key = 'same-key';

        $process1 = new Process(['php', $workerPath, $key]);
        $process2 = new Process(['php', $workerPath, $key]);

        $process1->start();
        \usleep(100000);

        $process2->start();

        $process1->wait();
        $process2->wait();

        $output1 = $process1->getOutput();
        $output2 = $process2->getOutput();

        $acquiredCount = \substr_count($output1.$output2, "acquired:{$key}\n");
        $this->assertSame(1, $acquiredCount);
    }

    #[TestDox('exclusive lock between two processes (requires Docker Redis)')]
    public function testExclusiveLockBetweenTwoProcessesWithDifferentKey(): void
    {
        $workerPath = __DIR__.'/../Fixtures/lock_worker.php';

        $key1 = 'key1';
        $key2 = 'key2';

        $process1 = new Process(['php', $workerPath, $key1]);
        $process2 = new Process(['php', $workerPath, $key2]);

        $process1->start();
        $process2->start();

        $process1->wait();
        $process2->wait();

        $output1 = $process1->getOutput();
        $output2 = $process2->getOutput();

        $expectedOutput = "acquired:{$key1}\nacquired:{$key2}\n";

        $acquiredCount = \substr_count($output1.$output2, $expectedOutput);
        $this->assertSame(1, $acquiredCount);
    }

    #[TestDox('multiTransactional should succeed with multiple keys (requires Docker Redis)')]
    #[DataProvider('provideClient')]
    public function testMultiTransactionalSuccess(RedisClient $client): void
    {
        $manager = new LockManager($client, 'feature:test:', 0);

        $client->set('mkey1', 'value1');
        $client->set('mkey2', 'value2');

        $manager->multiTransactional(['mkey1', 'mkey2'], function () use ($client): void {
            $client->set('mkey1', 'value10');
            $client->set('mkey2', 'value20');
        });

        $this->assertSame('value10', $client->get('mkey1'));
        $this->assertSame('value20', $client->get('mkey2'));
    }

    #[TestDox('multiTransactional rollback should succeed with multiple keys (requires Docker Redis)')]
    #[DataProvider('provideClient')]
    public function testMultiTransactionalRollbackWithException(RedisClient $client): void
    {
        $manager = new LockManager($client, 'feature:test:', 0);

        $client->set('mkey1', 'value1');
        $client->set('mkey2', 'value2');

        try {
            $manager->multiTransactional(['mkey1', 'mkey2'], function () use ($client): void {
                $client->set('mkey1', 'value10');
                $client->set('mkey2', 'value20');

                throw new \RuntimeException('fail');
            });
        } catch (\Throwable) {
        }

        $this->assertSame('value1', $client->get('mkey1'));
        $this->assertSame('value2', $client->get('mkey2'));
    }

    #[TestDox('exclusive multi-key lock between two processes should block one (requires Docker Redis)')]
    public function testExclusiveMultiTransactionalLockBetweenProcesses(): void
    {
        $worker = __DIR__.'/../Fixtures/multi_lock_worker.php';

        $keys = ['lock1', 'lock2'];

        $process1 = new Process(['php', $worker, \implode(',', $keys)]);
        $process2 = new Process(['php', $worker, \implode(',', $keys)]);

        $process1->start();
        \usleep(100000);

        $process2->start();

        $process1->wait();
        $process2->wait();

        $output1 = $process1->getOutput();
        $output2 = $process2->getOutput();

        $acquiredCount = \substr_count($output1.$output2, 'acquired:lock1,lock2');

        $this->assertSame(1, $acquiredCount);
    }

    #[TestDox('different multi-key locks in different processes should succeed (requires Docker Redis)')]
    public function testMultiTransactionalSucceedsInDifferentProcessesWithDifferentKeys(): void
    {
        $worker = __DIR__.'/../Fixtures/multi_lock_worker.php';

        $process1 = new Process(['php', $worker, 'lockA1,lockA2']);
        $process2 = new Process(['php', $worker, 'lockB1,lockB2']);

        $process1->start();
        $process2->start();

        $process1->wait();
        $process2->wait();

        $output1 = $process1->getOutput();
        $output2 = $process2->getOutput();

        $this->assertStringContainsString('acquired:lockA1,lockA2', $output1.$output2);
        $this->assertStringContainsString('acquired:lockB1,lockB2', $output1.$output2);
    }

    /**
     * Creates a PhpRedis connection.
     */
    private static function createPhpRedisConnection(): PhpRedis
    {
        $redis = new PhpRedis();
        $redis->connect('redis-exclusive-redis', 6379);

        return $redis;
    }

    /**
     * Creates a Predis connection.
     */
    private static function createPredisConnection(): Predis
    {
        return new Predis([
            'scheme' => 'tcp',
            'host' => 'redis-exclusive-redis',
            'port' => 6379,
        ]);
    }
}
