<?php

namespace Tests\Mock\Clients;

use M6Web\Component\RedisMock\RedisMock as M6WebRedisMock;

/**
 * Mock Redis client for testing.
 */
class RedisMock extends M6WebRedisMock
{
    /**
     * @var array<string, \Closure>
     */
    protected array $scriptHandlers = [];

    /**
     * Register a Lua script mock handler.
     */
    public function registerEval(string $script, \Closure $handler): void
    {
        $this->scriptHandlers[$script] = $handler;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed ...$arguments
     */
    #[\Override]
    public function eval($script, $numberOfKeys, ...$arguments): mixed
    {
        if (!isset($this->scriptHandlers[$script])) {
            throw new \RuntimeException('Lua script not registered for eval.');
        }

        $keys = \array_slice($arguments, 0, $numberOfKeys);
        $args = \array_slice($arguments, $numberOfKeys);

        return ($this->scriptHandlers[$script])($keys, $args);
    }
}
