<?php

namespace Lihs\RedisExclusive;

/**
 * Transaction result.
 *
 * @template T
 */
final class TransactionResult
{
    /**
     * Constructor.
     *
     * @param ?T $result
     */
    public function __construct(
        private readonly bool $acquired,
        private readonly mixed $result = null,
    ) {}

    /**
     * Get the result (only valid if acquired).
     *
     * @return ?T
     */
    public function result(): mixed
    {
        return $this->result;
    }

    /**
     * Check if the lock was acquired.
     *
     * @phpstan-assert-if-true !null $this->result
     */
    public function isAcquired(): bool
    {
        return $this->acquired;
    }
}
