<?php

namespace Lihs\RedisExclusive\Providers;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lihs\RedisExclusive\Clients;
use Lihs\RedisExclusive\Clients\Option;
use Lihs\RedisExclusive\LockManager;
use Predis\Client as Predis;
use Redis as PhpRedis;

/**
 * Redis Exclusive Lock Service Provider for Laravel.
 */
class RedisClientServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/redis-exclusive.php',
            'redis-exclusive'
        );

        // Register Redis Client
        $this->app->singleton(Clients\RedisClient::class, function (Application $app): Clients\RedisClient {
            /** @var Config */
            $config = $app->make('config');

            $driver = $config->get('redis-exclusive.driver', 'phpredis');

            return match ($driver) {
                'predis' => new Clients\PredisClient(
                    new Predis($config->get('redis-exclusive.redis', [])),
                    new Option\OptionDispatcher(
                        new Option\Predis\SetAdaptor(),
                    )
                ),
                'phpredis' => new Clients\PhpRedisClient(
                    $this->createPhpRedisInstance($config),
                    new Option\OptionDispatcher(
                        new Option\PhpRedis\SetAdaptor(),
                    )
                ),
                default => throw new \InvalidArgumentException('Unsupported Redis client driver.'),
            };
        });

        // Register Lock Manager
        $this->app->singleton(LockManager::class, function (Application $app): LockManager {
            return new LockManager($app->make(Clients\RedisClient::class));
        });
    }

    /**
     * Bootstrap the service.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/redis-exclusive.php' => $this->app->configPath('redis-exclusive.php'),
        ], 'redis-exclusive-config');
    }

    /**
     * Create and configure PhpRedis instance.
     */
    private function createPhpRedisInstance(Config $config): PhpRedis
    {
        $redis = new PhpRedis();

        /** @var array<string, mixed> $redisConfig */
        $redisConfig = $config->get('redis-exclusive.redis', []);

        $host = \is_string($redisConfig['host'] ?? null) ? $redisConfig['host'] : '127.0.0.1';
        $port = \is_int($redisConfig['port'] ?? null) ? $redisConfig['port'] : 6379;
        $password = \is_string($redisConfig['password'] ?? null) ? $redisConfig['password'] : null;
        $database = \is_int($redisConfig['database'] ?? null) ? $redisConfig['database'] : 0;

        $redis->connect($host, $port);

        if (null !== $password) {
            $redis->auth($password);
        }

        $redis->select($database);

        return $redis;
    }
}
