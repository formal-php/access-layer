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
final class Delete implements Builder
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

    #[\Override]
    public function normalize(Driver $driver): Query
    {
        return Query::of(
            match ($driver) {
                Driver::mysql => $this->mysqlSql($driver),
                Driver::postgres => $this->postgresSql($driver),
            },
            $this->where->parameters(),
        );
    }

    /**
     * @return non-empty-string
     */
    private function mysqlSql(Driver $driver): string
    {
        /** @var non-empty-string */
        return \sprintf(
            'DELETE %s FROM %s%s %s',
            match (true) {
                $this->table instanceof Name\Aliased => $driver->escapeName($this->table->alias()),
                default => $this->table->sql($driver),
            },
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

    /**
     * @return non-empty-string
     */
    private function postgresSql(Driver $driver): string
    {
        $joins = '';
        $where = $this->where->sql($driver);

        if (!$this->joins->empty()) {
            $joins = Str::of(', ')
                ->join($this->joins->map(
                    static fn($join) => $join->table()->sql($driver),
                ))
                ->prepend(' USING ')
                ->toString();
            $where = match ($where) {
                '' => $this
                    ->joins
                    ->flatMap(static fn($join) => $join->condition()->toSequence())
                    ->map(static function($condition) use ($driver) {
                        [$left, $right] = $condition;

                        return \sprintf(
                            '%s = %s AND',
                            $left->sql($driver),
                            $right->sql($driver),
                        );
                    })
                    ->map(Str::of(...))
                    ->fold(new Concat)
                    ->dropEnd(4)
                    ->prepend('WHERE ')
                    ->toString(),
                default => $this
                    ->joins
                    ->flatMap(static fn($join) => $join->condition()->toSequence())
                    ->map(static function($condition) use ($driver) {
                        [$left, $right] = $condition;

                        return \sprintf(
                            'AND %s = %s',
                            $left->sql($driver),
                            $right->sql($driver),
                        );
                    })
                    ->map(Str::of(...))
                    ->fold(new Concat)
                    ->drop(4)
                    ->prepend(' AND (')
                    ->append(')')
                    ->prepend($where)
                    ->toString(),
            };
        }

        /** @var non-empty-string */
        return \sprintf(
            'DELETE FROM %s%s %s',
            $this->table->sql($driver),
            $joins,
            $where,
        );
    }
}
