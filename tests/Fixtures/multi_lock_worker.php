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

$keys = explode(',', $argv[1] ?? '');

$manager->multiTransactional($keys, function () use ($keys): void {
    echo 'acquired:'.implode(',', $keys)."\n";
    sleep(1);
});
