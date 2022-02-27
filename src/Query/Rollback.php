<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\Query;
use Innmind\Immutable\Sequence;

final class Rollback implements Query
{
    public function parameters(): Sequence
    {
        /** @var Sequence<Parameter> */
        return Sequence::of();
    }

    public function sql(): string
    {
        return 'ROLLBACK';
    }
}
