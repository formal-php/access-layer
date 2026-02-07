<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query\Constraint;

use Formal\AccessLayer\{
    Table\Column,
    Driver,
};
use Innmind\Immutable\{
    Maybe,
    Sequence,
    Str,
    Monoid\Concat,
};

/**
 * @psalm-immutable
 */
final class Unique
{
    private Column\Name $column;
    /** @var Sequence<Column\Name> */
    private Sequence $columns;
    /** @var Maybe<non-empty-string> */
    private Maybe $name;

    /**
     * @param Sequence<Column\Name> $columns
     * @param Maybe<non-empty-string> $name
     */
    private function __construct(
        Column\Name $column,
        Sequence $columns,
        Maybe $name,
    ) {
        $this->column = $column;
        $this->columns = $columns;
        $this->name = $name;
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     */
    public static function of(
        Column\Name $column,
        Column\Name ...$columns,
    ): self {
        /** @var Maybe<non-empty-string> */
        $name = Maybe::nothing();

        return new self($column, Sequence::of(...$columns), $name);
    }

    /**
     * @param non-empty-string $name
     */
    public function named(string $name): self
    {
        return new self(
            $this->column,
            $this->columns,
            Maybe::just($name),
        );
    }

    /**
     * @return non-empty-string
     */
    public function sql(Driver $driver): string
    {
        $columns = $this
            ->columns
            ->map(static fn($column) => ', '.$column->sql($driver))
            ->map(Str::of(...))
            ->fold(Concat::monoid)
            ->toString();

        return $this->name->match(
            fn($name) => \sprintf(
                'CONSTRAINT UC_%s UNIQUE (%s%s)',
                $name,
                $this->column->sql($driver),
                $columns,
            ),
            fn() => \sprintf(
                'UNIQUE (%s%s)',
                $this->column->sql($driver),
                $columns,
            ),
        );
    }
}
