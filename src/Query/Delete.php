<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Query\Select\Join,
    Table\Name,
    Driver,
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
    private Name|Name\Aliased $table;
    /** @var Sequence<Join> */
    private Sequence $joins;
    private Where $where;

    /**
     * @param Sequence<Join> $joins
     */
    private function __construct(
        Name|Name\Aliased $table,
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
    public static function from(Name|Name\Aliased $table): self
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

    public function sql(Driver $driver): string
    {
        /** @var non-empty-string */
        return \sprintf(
            'DELETE FROM %s%s %s',
            $this->table->sql($driver),
            $this
                ->joins
                ->map(static fn($join) => $join->sql($driver))
                ->map(Str::of(...))
                ->fold(new Concat)
                ->toString(),
            $this->where->sql($driver),
        );
    }

    public function lazy(): bool
    {
        return false;
    }
}
