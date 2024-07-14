<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Driver,
};
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class StartTransaction implements Query
{
    public function parameters(): Sequence
    {
        /** @var Sequence<Parameter> */
        return Sequence::of();
    }

    public function sql(Driver $driver): string
    {
        return 'START TRANSACTION';
    }

    public function lazy(): bool
    {
        return false;
    }
}
