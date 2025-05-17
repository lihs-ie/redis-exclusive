<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[Group("unit")]
final class ExampleTest extends TestCase
{
    #[TestDox("Example test")]
    public function testExample(): void
    {
        $closure = fn (int $index): bool => $index === 0;

        $this->assertTrue($closure(0));
    }
}
