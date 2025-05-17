<?php

namespace Tests\Mock\Clients\Option;

use Lihs\RedisExclusive\Clients\Option\OptionAdaptor;

/**
 * Adaptor for RedisMock SET command options.
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
     * @return array{null|'ex'|'exat'|'px'|'pxat', null|int, null|'get'|'nx'|'xx'}
     */
    public function adapt(string $command, array $options): array
    {
        $ttl = null;
        $expire = null;
        $flag = null;

        foreach ($options as $field => $value) {
            $key = \strtoupper($field);

            if (\in_array($key, self::HAS_EXPIRE, true) && \is_integer($value)) {
                $expire = \mb_strtolower($key);
                $ttl = $value;
            }

            if (\in_array($key, self::HAS_FLAG, true) && true === $value) {
                $flag = \mb_strtolower($key);
            }
        }

        return [$expire, $ttl, $flag];
    }
}
