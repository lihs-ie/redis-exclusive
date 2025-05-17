<?php

namespace Tests\Unit\Option;

use Lihs\RedisExclusive\Clients\Option\OptionAdaptor;
use Lihs\RedisExclusive\Clients\Option\OptionDispatcher;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('unit')]
#[Group('option')]
#[CoversNothing]
final class OptionDispatcherTest extends TestCase
{
    #[TestDox('dispatch should return the adapted options')]
    public function testDispatchShouldReturnAdaptedOptions(): void
    {
        $index = \mt_rand(2, 255);

        $targetCommand = (string) $index;

        $adaptors = \array_map(
            fn (int $index): OptionAdaptor => $this->createAdaptor((string) $index),
            \range(0, $index)
        );

        $dispatcher = new OptionDispatcher(...$adaptors);

        $expected = \range(0, 10);
        $adaptedOptions = $dispatcher->dispatch($targetCommand, $expected);

        $this->assertSame($expected, $adaptedOptions);
    }

    #[TestDox('dispatch throws an exception for unsupported command')]
    public function testDispatchShouldThrowExceptionForUnsupportedCommand(): void
    {
        $index = \mt_rand(2, 255);

        $adaptors = \array_map(
            fn (int $index): OptionAdaptor => $this->createAdaptor((string) $index),
            \range(0, $index)
        );

        $dispatcher = new OptionDispatcher(...$adaptors);

        $this->expectException(\RuntimeException::class);

        $dispatcher->dispatch((string) ($index + 1), []);
    }

    /**
     * Creates an Adaptor for the given command.
     */
    private function createAdaptor(string $targetCommand): OptionAdaptor
    {
        return new class($targetCommand) implements OptionAdaptor {
            public function __construct(private readonly string $targetCommand) {}

            /**
             * {@inheritDoc}
             */
            public function support(string $command): bool
            {
                return $this->targetCommand === $command;
            }

            /**
             * {@inheritDoc}
             *
             * @param array<string> $options
             */
            public function adapt(string $command, array $options): array
            {
                return $options;
            }
        };
    }
}
