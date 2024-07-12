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
    private Row $row;

    /**
     * @no-named-arguments
     */
    private function __construct(Name $table, Row $row)
    {
        $this->table = $table;
        $this->row = $row;
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @return Sequence<self>
     */
    public static function into(Name $table, Row $first, Row ...$rest): Sequence
    {
        return Sequence::of($first, ...$rest)->map(static fn($row) => new self(
            $table,
            $row,
        ));
    }

    public function parameters(): Sequence
    {
        return $this->row->values()->map(
            static fn($value) => Parameter::of($value->value(), $value->type()),
        );
    }

    public function sql(Driver $driver): string
    {
        return $this->buildInsert($driver);
    }

    public function lazy(): bool
    {
        return false;
    }

    /**
     * @return non-empty-string
     */
    private function buildInsert(Driver $driver): string
    {
        /** @var Sequence<string> */
        $keys = $this->row->values()->map(static fn($value) => $value->columnSql($driver));
        /** @var Sequence<string> */
        $values = $this->row->values()->map(static fn() => '?');

        return \sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table->sql($driver),
            Str::of(', ')->join($keys)->toString(),
            Str::of(', ')->join($values)->toString(),
        );
    }
}
