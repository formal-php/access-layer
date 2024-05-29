<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Query\Select\Direction,
    Query\Select\Join,
    Table\Name,
    Table\Column,
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
    private Name|Name\Aliased $table;
    private bool $lazy;
    /** @var Sequence<Join> */
    private Sequence $joins;
    /** @var Sequence<Column\Name|Column\Name\Namespaced|Column\Name\Aliased> */
    private Sequence $columns;
    /** @var Maybe<non-empty-string> */
    private Maybe $count;
    private Where $where;
    /** @var ?array{Column\Name|Column\Name\Namespaced|Column\Name\Aliased, Direction} */
    private ?array $orderBy;
    /** @var ?positive-int */
    private ?int $limit;
    /** @var ?positive-int */
    private ?int $offset;

    /**
     * @param Sequence<Join> $joins
     * @param Sequence<Column\Name|Column\Name\Namespaced|Column\Name\Aliased> $columns
     * @param Maybe<non-empty-string> $count
     * @param ?array{Column\Name|Column\Name\Namespaced|Column\Name\Aliased, Direction} $orderBy
     * @param ?positive-int $limit
     * @param ?positive-int $offset
     */
    private function __construct(
        Name|Name\Aliased $table,
        bool $lazy,
        Sequence $joins,
        Sequence $columns,
        Maybe $count,
        Where $where,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ) {
        $this->table = $table;
        $this->lazy = $lazy;
        $this->joins = $joins;
        $this->columns = $columns;
        $this->count = $count;
        $this->where = $where;
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->offset = $offset;
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
    public function limit(int $limit, int $offset = null): self
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

    public function parameters(): Sequence
    {
        return $this->where->parameters();
    }

    public function sql(): string
    {
        /** @var non-empty-string */
        return \sprintf(
            'SELECT %s FROM %s%s %s%s%s%s',
            $this->count->match(
                static fn($alias) => "COUNT(1) AS `$alias`",
                fn() => $this->columns->empty() ? '*' : $this->buildColumns(),
            ),
            $this->table->sql(),
            $this
                ->joins
                ->map(static fn($join) => $join->sql())
                ->map(Str::of(...))
                ->fold(new Concat)
                ->toString(),
            $this->where->sql(),
            match ($this->orderBy) {
                null => '',
                default => \sprintf(
                    ' ORDER BY %s %s',
                    $this->orderBy[0]->sql(),
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

    public function lazy(): bool
    {
        return $this->lazy;
    }

    private function buildColumns(): string
    {
        $columns = $this->columns->map(
            static fn($column) => $column->sql(),
        );

        /** @psalm-suppress InvalidArgument Because non-empty-string instead of string */
        return Str::of(', ')->join($columns)->toString();
    }
}
