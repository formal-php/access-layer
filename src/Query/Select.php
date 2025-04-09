<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Query\Select\Direction,
    Query\Select\Join,
    Table\Name,
    Table\Column,
    Driver,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Sequence,
    Str,
    Monoid\Concat,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Select implements Query
{
    /**
     * @param Sequence<Join> $joins
     * @param Sequence<Column\Name|Column\Name\Namespaced|Column\Name\Aliased> $columns
     * @param Maybe<non-empty-string> $count
     * @param ?array{Column\Name|Column\Name\Namespaced|Column\Name\Aliased, Direction} $orderBy
     * @param ?positive-int $limit
     * @param ?positive-int $offset
     */
    private function __construct(
        private Name|Name\Aliased $table,
        private bool $lazy,
        private Sequence $joins,
        private Sequence $columns,
        private Maybe $count,
        private Where $where,
        private ?array $orderBy,
        private ?int $limit,
        private ?int $offset,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function from(Name|Name\Aliased $table): self
    {
        /** @var Maybe<non-empty-string> */
        $count = Maybe::nothing();

        return new self(
            $table,
            false,
            Sequence::of(),
            Sequence::of(),
            $count,
            Where::everything(),
            null,
            null,
            null,
        );
    }

    /**
     * @psalm-pure
     */
    public static function onDemand(Name|Name\Aliased $table): self
    {
        /** @var Maybe<non-empty-string> */
        $count = Maybe::nothing();

        return new self(
            $table,
            true,
            Sequence::of(),
            Sequence::of(),
            $count,
            Where::everything(),
            null,
            null,
            null,
        );
    }

    public function join(Join $join): self
    {
        return new self(
            $this->table,
            $this->lazy,
            ($this->joins)($join),
            $this->columns,
            $this->count,
            $this->where,
            $this->orderBy,
            $this->limit,
            $this->offset,
        );
    }

    /**
     * @no-named-arguments
     */
    public function columns(
        Column\Name|Column\Name\Namespaced|Column\Name\Aliased $first,
        Column\Name|Column\Name\Namespaced|Column\Name\Aliased ...$rest,
    ): self {
        /** @var Maybe<non-empty-string> */
        $count = Maybe::nothing();

        return new self(
            $this->table,
            $this->lazy,
            $this->joins,
            Sequence::of($first, ...$rest),
            $count,
            $this->where,
            $this->orderBy,
            $this->limit,
            $this->offset,
        );
    }

    /**
     * @param non-empty-string $alias
     */
    public function count(string $alias): self
    {
        return new self(
            $this->table,
            $this->lazy,
            $this->joins,
            $this->columns->clear(),
            Maybe::just($alias),
            $this->where,
            $this->orderBy,
            $this->limit,
            $this->offset,
        );
    }

    public function where(Specification $specification): self
    {
        return new self(
            $this->table,
            $this->lazy,
            $this->joins,
            $this->columns,
            $this->count,
            Where::of($specification),
            $this->orderBy,
            $this->limit,
            $this->offset,
        );
    }

    public function orderBy(
        Column\Name|Column\Name\Namespaced|Column\Name\Aliased $column,
        Direction $direction,
    ): self {
        return new self(
            $this->table,
            $this->lazy,
            $this->joins,
            $this->columns,
            $this->count,
            $this->where,
            [$column, $direction],
            $this->limit,
            $this->offset,
        );
    }

    /**
     * @param positive-int $limit
     * @param positive-int $offset
     */
    public function limit(int $limit, ?int $offset = null): self
    {
        return new self(
            $this->table,
            $this->lazy,
            $this->joins,
            $this->columns,
            $this->count,
            $this->where,
            $this->orderBy,
            $limit,
            $offset,
        );
    }

    #[\Override]
    public function parameters(): Sequence
    {
        return $this->where->parameters();
    }

    #[\Override]
    public function sql(Driver $driver): string
    {
        /** @var non-empty-string */
        return \sprintf(
            'SELECT %s FROM %s%s %s%s%s%s',
            $this
                ->count
                ->map($driver->escapeName(...))
                ->match(
                    static fn($alias) => "COUNT(1) AS $alias",
                    fn() => $this->columns->empty() ? '*' : $this->buildColumns($driver),
                ),
            $this->table->sql($driver),
            $this
                ->joins
                ->map(static fn($join) => $join->sql($driver))
                ->map(Str::of(...))
                ->fold(new Concat)
                ->toString(),
            $this->where->sql($driver),
            match ($this->orderBy) {
                null => '',
                default => \sprintf(
                    ' ORDER BY %s %s',
                    $this->orderBy[0]->sql($driver),
                    $this->orderBy[1]->sql(),
                ),
            },
            match ($this->limit) {
                null => '',
                default => ' LIMIT '.$this->limit,
            },
            match ($this->offset) {
                null => '',
                default => ' OFFSET '.$this->offset,
            },
        );
    }

    #[\Override]
    public function lazy(): bool
    {
        return $this->lazy;
    }

    private function buildColumns(Driver $driver): string
    {
        $columns = $this->columns->map(
            static fn($column) => $column->sql($driver),
        );

        /** @psalm-suppress InvalidArgument Because non-empty-string instead of string */
        return Str::of(', ')->join($columns)->toString();
    }
}
