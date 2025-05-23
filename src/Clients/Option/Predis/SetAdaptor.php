<?php

namespace Lihs\RedisExclusive\Clients\Option\Predis;

use Lihs\RedisExclusive\Clients\Option\OptionAdaptor;

/**
 * Adaptor for Predis SET command options.
 */
final class SetAdaptor implements OptionAdaptor
{
    /**
     * Has expire options.
     *
     * @var array{'EX', 'PX', 'EXAT', 'PXAT'}
     */
    private const array HAS_EXPIRE = ['EX', 'PX', 'EXAT', 'PXAT'];

    /**
     * Has flag options.
     *
     * @var array{'NX', 'XX', 'GET'}
     */
    private const array HAS_FLAG = ['NX', 'XX', 'GET'];

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
     * @return array{null|string, null|int, null|string}
     */
    public function adapt(string $command, array $options): array
    {
        $expireResolution = null;
        $expireTTL = null;
        $flag = null;

        foreach ($options as $field => $value) {
            $key = \strtoupper($field);

            if (\in_array($key, self::HAS_EXPIRE, true) && \is_integer($value)) {
                $expireResolution = $key;
                $expireTTL = $value;
            }

            if (\in_array($key, self::HAS_FLAG, true) && true === $value) {
                $flag = $key;
            }
        }

        return [$expireResolution, $expireTTL, $flag];
    }
}
