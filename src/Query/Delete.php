<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Query\Select\Join,
    Query\Parameter,
    Query\Parameter\Type,
    Table\Name,
    Table\Column,
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
final class Delete implements Query
{
    private Name $table;
    /** @var Sequence<Join> */
    private Sequence $joins;
    private Where $where;

    /**
     * @param Sequence<Join> $joins
     */
    private function __construct(
        Name $table,
        Sequence $joins,
        Where $where,
    ) {
        $this->table = $table;
        $this->joins = $joins;
        $this->where = $where;
    }

    /**
     * @psalm-pure
     */
    public static function from(Name $table): self
    {
        return new self($table, Sequence::of(), Where::everything());
    }

    public function join(Join $join): self
    {
        return new self(
            $this->table,
            ($this->joins)($join),
            $this->where,
        );
    }

    public function where(Specification $specification): self
    {
        return new self(
            $this->table,
            $this->joins,
            Where::of($specification),
        );
    }

    public function parameters(): Sequence
    {
        return $this->where->parameters();
    }

    public function sql(): string
    {
        /** @var Sequence<Name|Name\Aliased> */
        $tables = Sequence::of($this->table);
        $tables = $tables
            ->append($this->joins->map(static fn($join) => $join->table()))
            ->map(static fn($table) => match (true) {
                $table instanceof Name\Aliased => "`{$table->alias()}`",
                default => $table->sql(),
            });

        /** @var non-empty-string */
        return \sprintf(
            'DELETE %s FROM %s%s %s',
            Str::of(', ')->join($tables)->toString(),
            $this->table->sql(),
            $this->joins->match(
                static fn($join, $rest) => $join->sql().$rest
                    ->map(static fn($join) => $join->sql())
                    ->map(Str::of(...))
                    ->fold(new Concat)
                    ->toString(),
                static fn() => '',
            ),
            $this->where->sql(),
        );
    }

    public function lazy(): bool
    {
        return false;
    }
}
