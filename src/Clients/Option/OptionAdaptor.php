<?php

namespace Lihs\RedisExclusive\Clients\Option;

/**
 * Interface for adapting unified Redis command options
 * into Redis client formats (e.g. PhpRedis, Predis).
 */
interface OptionAdaptor
{
    /**
     * Check if the adaptor supports the given command.
     */
    public function support(string $command): bool;

    /**
     * Adapt the options for the given command.
     *
     * @template O
     *
     * @param array<O> $options
     *
     * @return array<string>
     */
    public function adapt(string $command, array $options): array;
}
