<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Query\Select\Join,
    Table\Name,
    Row,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Sequence,
    Str,
    Monoid\Concat,
};

/**
 * @psalm-immutable
 */
final class Update implements Query
{
    private Name|Name\Aliased $table;
    private Row $row;
    /** @var Sequence<Join> */
    private Sequence $joins;
    private Where $where;

    /**
     * @param Sequence<Join> $joins
     */
    private function __construct(
        Name|Name\Aliased $table,
        Row $row,
        Sequence $joins,
        Where $where,
    ) {
        $this->table = $table;
        $this->row = $row;
        $this->joins = $joins;
        $this->where = $where;
    }

    /**
     * @psalm-pure
     */
    public static function set(Name|Name\Aliased $table, Row $row): self
    {
        return new self($table, $row, Sequence::of(), Where::everything());
    }

    public function join(Join $join): self
    {
        return new self(
            $this->table,
            $this->row,
            ($this->joins)($join),
            $this->where,
        );
    }

    public function where(Specification $specification): self
    {
        return new self(
            $this->table,
            $this->row,
            $this->joins,
            Where::of($specification),
        );
    }

    public function parameters(): Sequence
    {
        return $this
            ->row
            ->values()
            ->map(static fn($value) => Parameter::of($value->value(), $value->type()))
            ->append($this->where->parameters());
    }

    public function sql(): string
    {
        /** @var Sequence<string> */
        $columns = $this
            ->row
            ->values()
            ->map(static fn($value) => "{$value->columnSql()} = ?");

        /** @var non-empty-string */
        return \sprintf(
            'UPDATE %s%s SET %s %s',
            $this->table->sql(),
            $this
                ->joins
                ->map(static fn($join) => $join->sql())
                ->map(Str::of(...))
                ->fold(new Concat)
                ->toString(),
            Str::of(', ')->join($columns)->toString(),
            $this->where->sql(),
        );
    }

    public function lazy(): bool
    {
        return false;
    }
}
