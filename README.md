# Redis-Exclusive

A simple and reliable Redis-based **exclusive lock (mutex)** library for Laravel.  
Supports both **Predis** and **PhpRedis** via Laravel's Redis abstraction layer.

---

## 📑 Table of Contents

- 🔧 [Features](#features)
- 📦 [Installation](#installation)
- 🧪 [Usage](#usage)
- ⚙️ [Configuration](#configuration)
- 📚 [Requirements](#requirements)
- 🧾 [Testing](#testing)
- 📄 [License](#license)

---

## Features

- Exclusive lock using Redis `SET NX PX`
- Compatible with both Predis and PhpRedis
- Atomic unlock via Lua script
- Laravel integration via Service Provider (optional)
- Lock execution with closure support
- Testable and PSR-4 compliant

---

## Installation

Install the package via Composer:

```bash
composer require lihs/redis-exclusive
```

### Laravel Auto-Discovery

The package will automatically register its service provider and facade in Laravel 11+.

### Manual Registration (if needed)

If auto-discovery is disabled, add the service provider to your `config/app.php`:

```php
'providers' => [
    // ...
    Lihs\RedisExclusive\Providers\RedisClientServiceProvider::class,
],

'aliases' => [
    // ...
    'RedisExclusive' => Lihs\RedisExclusive\Facades\RedisExclusive::class,
],
```

## Usage

### Acquiring and releasing a lock manually

```php
use Lihs\RedisExclusive\LockManager;

$lock = \app(LockManager::class)->lock('resource-key', ttl: 10000, owner: 'uuid-1234');

if ($lock->acquire()) {
    try {
        // Execute exclusive logic
    } finally {
        $lock->release();
    }
}
```

Advanced: Rollback-safe transactional Redis lock
Use LockManager::transactionalLock() to obtain a lock that can track and restore Redis state:

```php
use Lihs\RedisExclusive\LockManager;
use Illuminate\Support\Facades\Redis;

$lockManager = new LockManager(Redis::connection());
$lock = $lockManager->transactionalLock('job:lock', 10000, 'uuid-5678');

// Track Redis keys you plan to modify
$lock->trackKey('job:123:status');
$lock->trackKey('job:123:progress');

if ($lock->acquire()) {
    try {
        Redis::set('job:123:status', 'processing');
        Redis::set('job:123:progress', 10);

        $lock->release(); // Normally done here on success
    } catch (\Throwable $exception) {
        $lock->rollback(); // Restores previous values
        throw $exception;
    }
};
```

This provides a way to simulate Redis-level rollback by restoring key values saved at the time of acquisition.

## Using a lock with a closure

```php
$lock->acquireWith(function () {
    // Executed only if lock is acquired
});
```

### Using Facades (Recommended)

```php
use Lihs\RedisExclusive\Facades\RedisExclusive;

// Simple lock with automatic release
$result = RedisExclusive::lock('resource-key')->acquireWith(function () {
    // Your exclusive code here
    return 'success';
});

if ($result !== null) {
    // Lock was acquired and code executed
    echo $result; // 'success'
}
```

### Transactional Locks with Facades

```php
use Lihs\RedisExclusive\Facades\RedisExclusive;
use Illuminate\Support\Facades\Redis;

$result = RedisExclusive::transactional('job:lock')->acquireWith(function ($lock) {
    $lock->trackKey('job:123:status');
    $lock->trackKey('job:123:progress');

    Redis::set('job:123:status', 'processing');
    Redis::set('job:123:progress', 100);

    return 'completed';
});
```

### Multi-Key Locks

```php
use Lihs\RedisExclusive\Facades\RedisExclusive;

$result = RedisExclusive::multiLock(['user:123', 'account:456'])->acquireWith(function () {
    // All locks acquired - safe to proceed
    return transferFunds();
});
```

## Configuration

If using Laravel integration, publish the config file:

```bash
php artisan vendor:publish --tag=redis-exclusive-config
Example configuration (config/redis-exclusive.php):
```

```php
return [
    'prefix' => 'lock:',
    'default_ttl' => 10000, // in milliseconds
    'driver' => 'default',  // Redis connection name (ex. predis)
];
```

## Requirements

- PHP >= 8.4
- Laravel >= 11

## Testing

Run tests with PHPUnit:

```bash
vendor/bin/phpunit
```

## License

MIT License

Copyright (c) 2025 lihs

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
