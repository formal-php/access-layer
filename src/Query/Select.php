<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Query\Select\Direction,
    Query\Select\Join,
    Table\Name,
    Table\Column,
    Row,
    Driver,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Sequence,
    Str,
    Monoid\Concat,
    Maybe,
    Predicate\Instance,
};

/**
 * @psalm-immutable
 */
final class Select implements Builder
{
    /**
     * @param Sequence<Join> $joins
     * @param Sequence<Column\Name|Column\Name\Namespaced|Column\Name\Aliased|Row\Value> $columns
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
     * @deprecated Use ::lazily() instead
     */
    public static function onDemand(Name|Name\Aliased $table): self
    {
        return self::lazily($table);
    }

    /**
     * @psalm-pure
     */
    public static function lazily(Name|Name\Aliased $table): self
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
        Column\Name|Column\Name\Namespaced|Column\Name\Aliased|Row\Value $first,
        Column\Name|Column\Name\Namespaced|Column\Name\Aliased|Row\Value ...$rest,
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
    public function normalize(Driver $driver): Query
    {
        /** @var non-empty-string */
        $sql = \sprintf(
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
        $parameters = $this
            ->columns
            ->keep(Instance::of(Row\Value::class))
            ->map(static fn($value) => Parameter::of(
                $value->value(),
                $value->type(),
            ))
            ->append($this->where->parameters());

        return match ($this->lazy) {
            true => SQL::lazily($sql, $parameters),
            false => SQL::of($sql, $parameters),
        };
    }

    /**
     * @internal
     *
     * @return Sequence<Column\Name>
     */
    public function names(): Sequence
    {
        return $this->count->match(
            static fn($alias) => Sequence::of(Column\Name::of($alias)),
            fn() => $this->columns->map(static fn($column) => match (true) {
                $column instanceof Row\Value => $column->column(),
                $column instanceof Column\Name\Aliased => Column\Name::of(
                    $column->alias(),
                ),
                $column instanceof Column\Name\Namespaced => $column->column(),
                default => $column,
            }),
        );
    }

    private function buildColumns(Driver $driver): string
    {
        $columns = $this->columns->map(static fn($column) => match (true) {
            $column instanceof Row\Value => \sprintf(
                '? as %s',
                $column->column()->sql($driver),
            ),
            default => $column->sql($driver),
        });

        /** @psalm-suppress InvalidArgument Because non-empty-string instead of string */
        return Str::of(', ')->join($columns)->toString();
    }
}
