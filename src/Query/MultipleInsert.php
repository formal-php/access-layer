<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Table\Name,
    Table\Column,
    Row,
    Driver,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class MultipleInsert implements Builder
{
    private Name $table;
    /** @var Sequence<Column\Name> */
    private Sequence $columns;
    /** @var Sequence<Row> */
    private Sequence $rows;

    /**
     * @param Sequence<Column\Name> $columns
     * @param Sequence<Row> $rows
     */
    private function __construct(
        Name $table,
        Sequence $columns,
        Sequence $rows,
    ) {
        $this->table = $table;
        $this->columns = $columns;
        $this->rows = $rows;
    }

    /**
     * The number of values for each row must be the same as the columns and in
     * the same order otherwise the query will fail.
     *
     * @no-named-arguments
     * @psalm-pure
     *
     * @return callable(Sequence<Row>): self
     */
    public static function into(
        Name $table,
        Column\Name $first,
        Column\Name ...$rest,
    ): callable {
        return static fn(Sequence $rows) => new self(
            $table,
            Sequence::of($first, ...$rest),
            $rows,
        );
    }

    #[\Override]
    public function normalize(Driver $driver): Query
    {
        return Query::of(
            $this->buildInsert($driver),
            $this->rows->flatMap(static fn($row) => $row->values()->map(
                static fn($value) => Parameter::of($value->value(), $value->type()),
            )),
        );
    }

    /**
     * @return non-empty-string
     */
    private function buildInsert(Driver $driver): string
    {
        $keys = $this->columns->map(static fn($column) => $column->sql($driver));
        $values = $this->rows->map(
            static fn($row) => Str::of(', ')
                ->join($row->values()->map(static fn() => '?'))
                ->prepend('(')
                ->append(')')
                ->toString(),
        );

        return \sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->table->sql($driver),
            Str::of(', ')->join($keys)->toString(),
            Str::of(', ')->join($values)->toString(),
        );
    }
}
