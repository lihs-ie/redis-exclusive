<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Redis Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default Redis driver that will be used by the
    | Redis exclusive lock library. You may specify either "phpredis" or
    | "predis" depending on your preference and installed extensions.
    |
    */

    'driver' => env('REDIS_EXCLUSIVE_DRIVER', 'phpredis'),

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    |
    | This option controls which Redis connection from your database config
    | will be used for exclusive locks. You can specify any connection name
    | defined in your config/database.php file.
    |
    */

    'connection' => env('REDIS_EXCLUSIVE_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Lock Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be applied to all lock keys to avoid conflicts with
    | other Redis keys in your application. You can customize this to match
    | your application's naming conventions.
    |
    */

    'prefix' => env('REDIS_EXCLUSIVE_PREFIX', 'lock:'),

    /*
    |--------------------------------------------------------------------------
    | Default TTL
    |--------------------------------------------------------------------------
    |
    | This is the default time-to-live (TTL) for locks in milliseconds.
    | If a lock is not explicitly released, it will automatically expire
    | after this duration to prevent deadlocks.
    |
    */

    'default_ttl' => env('REDIS_EXCLUSIVE_DEFAULT_TTL', 10000),

    /*
    |--------------------------------------------------------------------------
    | Redis Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the Redis connection parameters specifically
    | for the exclusive lock library. These settings will override the
    | default Laravel Redis configuration when specified.
    |
    */

    'redis' => [
        'host' => env('REDIS_EXCLUSIVE_HOST', env('REDIS_HOST', '127.0.0.1')),
        'port' => env('REDIS_EXCLUSIVE_PORT', env('REDIS_PORT', 6379)),
        'password' => env('REDIS_EXCLUSIVE_PASSWORD', env('REDIS_PASSWORD')),
        'database' => env('REDIS_EXCLUSIVE_DATABASE', env('REDIS_DB', 0)),
    ],
];
