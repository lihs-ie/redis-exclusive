<?php

namespace Lihs\RedisExclusive\Exceptions;

/**
 * Exception thrown when a lock acquisition fails.
 */
final class LockAcquisitionException extends \RuntimeException
{
    /**
     * Constructor.
     */
    public function __construct(
        string $message = 'Failed to acquire lock',
    ) {
        parent::__construct($message);
    }
}
