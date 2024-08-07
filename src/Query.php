<?php
declare(strict_types = 1);

namespace Formal\AccessLayer;

use Formal\AccessLayer\Query\Parameter;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
interface Query
{
    /**
     * @return Sequence<Parameter>
     */
    public function parameters(): Sequence;

    /**
     * @return non-empty-string
     */
    public function sql(Driver $driver): string;
    public function lazy(): bool;
}
