<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Query\Parameter,
    Row,
    Table\Name,
    Table\Column,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class Select implements Query
{
    private Name|Name\Aliased $table;
    private bool $lazy;
    /** @var Sequence<Column\Name|Column\Name\Namespaced|Column\Name\Aliased> */
    private Sequence $columns;
    private Where $where;

    /**
     * @param Sequence<Column\Name|Column\Name\Namespaced|Column\Name\Aliased> $columns
     */
    private function __construct(
        Name|Name\Aliased $table,
        bool $lazy,
        Sequence $columns,
        Where $where,
    ) {
        $this->table = $table;
        $this->lazy = $lazy;
        $this->columns = $columns;
        $this->where = $where;
    }

    /**
     * @psalm-pure
     */
    public static function from(Name|Name\Aliased $table): self
    {
        return new self($table, false, Sequence::of(), Where::everything());
    }

    /**
     * @psalm-pure
     */
    public static function onDemand(Name|Name\Aliased $table): self
    {
        return new self($table, true, Sequence::of(), Where::everything());
    }

    /**
     * @no-named-arguments
     */
    public function columns(
        Column\Name|Column\Name\Namespaced|Column\Name\Aliased $first,
        Column\Name|Column\Name\Namespaced|Column\Name\Aliased ...$rest,
    ): self {
        return new self(
            $this->table,
            $this->lazy,
            Sequence::of($first, ...$rest),
            $this->where,
        );
    }

    public function where(Specification $specification): self
    {
        return new self(
            $this->table,
            $this->lazy,
            $this->columns,
            Where::of($specification),
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
            'SELECT %s FROM %s %s',
            $this->columns->empty() ? '*' : $this->buildColumns(),
            $this->table->sql(),
            $this->where->sql(),
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
