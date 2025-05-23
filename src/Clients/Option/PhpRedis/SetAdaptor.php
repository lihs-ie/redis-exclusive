<?php

namespace Lihs\RedisExclusive\Clients\Option\PhpRedis;

use Lihs\RedisExclusive\Clients\Option\OptionAdaptor;

/**
 * Adaptor for PhpRedis SET command options.
 */
final class SetAdaptor implements OptionAdaptor
{
    /**
     * {@inheritDoc}
     */
    public function support(string $command): bool
    {
        return 'SET' === $command;
    }

    /**
     * {@inheritDoc}
     *
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
     *
     * @return array<array-key, int|string>
     */
    public function adapt(string $command, array $options): array
    {
        $adapted = [];

        foreach ($options as $key => $value) {
            if (\is_bool($value)) {
                if ($value) {
                    $adapted[] = $key;
                }
            } else {
                $adapted[$key] = $value;
            }
        }

        return $adapted;
    }
}
