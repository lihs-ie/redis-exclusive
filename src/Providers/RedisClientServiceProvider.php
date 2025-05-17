<?php

namespace Lihs\RedisExclusive\Providers;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lihs\RedisExclusive\Clients;
use Lihs\RedisExclusive\Clients\Option;
use Predis\Client as Predis;
use Redis as PhpRedis;

/**
 * For Switchable Redis client by configuration.
 */
class RedisClientServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(Clients\RedisClient::class, function (Application $app): Clients\RedisClient {
            /** @var Config */
            $config = $app->make('config');

            $driver = $config->get('redis-exclusive.driver', 'phpredis');

            return match ($driver) {
                'predis' => new Clients\PredisClient(
                    new Predis(),
                    new Option\OptionDispatcher(
                        new Option\Predis\SetAdaptor(),
                    )
                ),
                'phpredis' => new Clients\PhpRedisClient(
                    new PhpRedis(),
                    new Option\OptionDispatcher(
                        new Option\PhpRedis\SetAdaptor(),
                    )
                ),
                default => throw new \InvalidArgumentException('Unsupported Redis client driver.'),
            };
        });
    }

    /**
     * Bootstrap the service.
     */
    public function boot(): void {}
}
