<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Table\Name,
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
final class Insert implements Query
{
    private Name $table;
    /** @var Sequence<Row> */
    private Sequence $rows;

    /**
     * @no-named-arguments
     */
    private function __construct(Name $table, Row $first, Row ...$rest)
    {
        $this->table = $table;
        $this->rows = Sequence::of($first, ...$rest);
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     */
    public static function into(Name $table, Row $first, Row ...$rest): self
    {
        return new self($table, $first, ...$rest);
    }

    public function parameters(): Sequence
    {
        return $this->rows->flatMap(static fn($row) => $row->values()->map(
            static fn($value) => Parameter::of($value->value(), $value->type()),
        ));
    }

    public function sql(Driver $driver): string
    {
        $inserts = $this->rows->map(
            fn($row) => $this->buildInsert($driver, $row),
        );

        /** @var non-empty-string Because there's at least one row */
        return Str::of('; ')->join($inserts)->toString();
    }

    public function lazy(): bool
    {
        return false;
    }

    private function buildInsert(Driver $driver, Row $row): string
    {
        /** @var Sequence<string> */
        $keys = $row->values()->map(static fn($value) => $value->columnSql($driver));
        /** @var Sequence<string> */
        $values = $row->values()->map(static fn() => '?');

        return \sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table->sql($driver),
            Str::of(', ')->join($keys)->toString(),
            Str::of(', ')->join($values)->toString(),
        );
    }
}
