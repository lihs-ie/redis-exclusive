<?php

namespace Lihs\RedisExclusive\Clients\Option;

/**
 * Dispatches Redis command options to the appropriate adaptor.
 */
final class OptionDispatcher
{
    /**
     * List of available command adaptors.
     *
     * @var array<OptionAdaptor>
     */
    private array $adaptors;

    /**
     * Constructor.
     */
    public function __construct(OptionAdaptor ...$adaptors)
    {
        $this->adaptors = $adaptors;
    }

    /**
     * Dispatch the command options to the appropriate adaptor.
     *
     * @template O
     *
     * @param array<O> $options
     *
     * @return array<string>
     */
    public function dispatch(string $command, array $options): array
    {
        foreach ($this->adaptors as $adaptor) {
            if ($adaptor->support($command)) {
                return $adaptor->adapt($command, $options);
            }
        }

        throw new \RuntimeException(\sprintf('Unsupported command: %s', $command));
    }
}
