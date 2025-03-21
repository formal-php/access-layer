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
final class Rollback implements Query
{
    #[\Override]
    public function parameters(): Sequence
    {
        /** @var Sequence<Parameter> */
        return Sequence::of();
    }

    #[\Override]
    public function sql(Driver $driver): string
    {
        return 'ROLLBACK';
    }

    #[\Override]
    public function lazy(): bool
    {
        return false;
    }
}
