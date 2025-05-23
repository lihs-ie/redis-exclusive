<?php

namespace Lihs\RedisExclusive\Clients;

/**
 * Interface for Redis clients.
 */
interface RedisClient
{
    /**
     * Select a Redis logical database.
     */
    public function select(int $dbNumber): bool;

    /**
     * Set a value.
     *
     * @template V
     *
     * @param V $value
     * @param array{
     *    EX?: int,
     *    PX?: int,
     *    NX?: bool,
     *    XX?: bool,
     *    EXAT?: int,
     *    PXAT?: int,
     *    KEEPTTL?: bool,
     *    GET?: bool,
     * } $options
     */
    public function set(string $key, mixed $value, array $options = []): bool;

    /**
     * Get a value.
     */
    public function get(string $key): mixed;

    /**
     * Remove a value.
     */
    public function remove(string ...$keys): int;

    /**
     * Evaluate a Lua script.
     *
     * @template A
     *
     * @param array<A> $args
     */
    public function eval(string $luaScript, array $args = [], int $numKeys = 0): mixed;

    /**
     * Begin a Redis transaction block.
     */
    public function multi(): void;

    /**
     * Execute queued commands in the transaction.
     *
     * @return array<mixed>
     */
    public function exec(): array;

    /**
     * Discard queued Redis commands in the transaction.
     */
    public function discard(): void;
}
