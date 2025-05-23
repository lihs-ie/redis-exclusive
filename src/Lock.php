<?php

namespace Lihs\RedisExclusive;

/**
 * Redis exclusive lock interface.
 */
interface Lock
{
    /**
     * Attempt to acquire the lock.
     */
    public function acquire(): bool;

    /**
     * Release the lock.
     */
    public function release(): bool;

    /**
     * Check if the lock is acquired.
     */
    public function isLocked(): bool;

    /**
     * Execute a callback only if the lock is acquired.
     *
     * @template R
     *
     * @param \Closure(): R $callback
     *
     * @return ?R
     */
    public function acquireWith(\Closure $callback): mixed;
}
