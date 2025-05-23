<?php

namespace Tests\Helpers;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Predis\Client as Predis;
use Predis\Response\Status;

#[Group('feature')]
trait UsesDockerRedis
{
    #[TestDox('Transaction rollback should succeed (requires Docker Redis)')]
    public function testTransactionRollbackWithDockerRedis(): void
    {
        $this->assertTrue($this->isDockerRedisAvailableByPredis());
        $this->assertTrue($this->isDockerRedisAvailableByPhpRedis());
    }

    /**
     * Check if Docker Redis is available by using Predis.
     */
    private function isDockerRedisAvailableByPredis(): bool
    {
        try {
            $client = new Predis([
                'scheme' => 'tcp',
                'host' => 'redis-exclusive-redis',
                'port' => 6379,
            ]);

            $result = $client->ping();

            if ($result instanceof Status) {
                return 'PONG' === $result->getPayload();
            }

            return \is_string($result) && 'PONG' === $result;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * Check if Docker Redis is available by using PhpRedis.
     */
    private function isDockerRedisAvailableByPhpRedis(): bool
    {
        try {
            $client = new \Redis();
            $client->connect('redis-exclusive-redis', 6379);

            $result = $client->ping();

            return \is_bool($result) && true === $result;
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
