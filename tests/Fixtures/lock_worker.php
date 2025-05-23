<?php

require 'vendor/autoload.php';

use Lihs\RedisExclusive\Clients\Option\OptionDispatcher;
use Lihs\RedisExclusive\Clients\Option\Predis\SetAdaptor;
use Lihs\RedisExclusive\Clients\PredisClient;
use Lihs\RedisExclusive\LockManager;
use Predis\Client as Predis;

$client = new Predis([
    'scheme' => 'tcp',
    'host' => 'redis-exclusive-redis',
    'port' => 6379,
]);

$manager = new LockManager(
    new PredisClient($client, new OptionDispatcher(new SetAdaptor())),
    'lock:test:',
    0
);

$key = $argv[1] ?? 'default';

$manager->transactional($key, function () use ($key): void {
    echo "acquired:{$key}\n";
    sleep(1);
});
