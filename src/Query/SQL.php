<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\Query;
use Innmind\Immutable\Sequence;

/**
 * @deprecated Use Query instead
 */
final class SQL
{
    /**
     * @psalm-pure
     * @deprecated Use Query::of() instead
     *
     * @param non-empty-string $sql
     * @param Sequence<Parameter> $parameters
     */
    public static function of(string $sql, ?Sequence $parameters = null): Query
    {
        return Query::of($sql, $parameters);
    }

    /**
     * @psalm-pure
     * @deprecated Use Query::lazily() instead
     *
     * @param non-empty-string $sql
     * @param Sequence<Parameter> $parameters
     */
    public static function onDemand(string $sql, ?Sequence $parameters = null): Query
    {
        return Query::lazily($sql, $parameters);
    }
}
