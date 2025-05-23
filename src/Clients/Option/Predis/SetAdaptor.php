<?php

namespace Lihs\RedisExclusive\Clients\Option\Predis;

use Lihs\RedisExclusive\Clients\Option\OptionAdaptor;

/**
 * Adaptor for Predis SET command options.
 */
final class SetAdaptor implements OptionAdaptor
{
    /**
     * {@inheritDoc}
     */
    public function support(string $command): bool
    {
        return 'SET' === \strtoupper($command);
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
     * @return array{null|int, null|int, null|string}
     */
    public function adapt(string $command, array $options): array
    {
        $expireResolution = null;
        $expireTTL = null;
        $flag = null;

        foreach ($options as $field => $value) {
            $key = \strtoupper($field);

            if (\in_array($key, ['EX', 'PX'], true) && \is_int($value)) {
                $expireResolution = 'EX' === $key ? 0 : 1;
                $expireTTL = $value;
            }

            if (\in_array($key, ['NX', 'XX', 'GET'], true) && true === $value) {
                $flag = $key;
            }
        }

        return [$expireResolution, $expireTTL, $flag];
    }
}
