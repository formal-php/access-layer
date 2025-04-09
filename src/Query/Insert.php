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
    private function __construct(
        private Name $table,
        private Row|Select $row,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function into(Name $table, Row|Select $row): self
    {
        return new self($table, $row);
    }

    #[\Override]
    public function parameters(): Sequence
    {
        if ($this->row instanceof Select) {
            return $this->row->parameters();
        }

        return $this->row->values()->map(
            static fn($value) => Parameter::of($value->value(), $value->type()),
        );
    }

    #[\Override]
    public function sql(Driver $driver): string
    {
        return $this->buildInsert($driver);
    }

    #[\Override]
    public function lazy(): bool
    {
        return false;
    }

    /**
     * @return non-empty-string
     */
    private function buildInsert(Driver $driver): string
    {
        if ($this->row instanceof Select) {
            $columns = $this->row->names();

            if ($columns->empty()) {
                throw new \LogicException('You need to specify the columns to select when inserting');
            }

            $keys = $columns->map(static fn($column) => $column->sql($driver));

            return \sprintf(
                'INSERT INTO %s (%s) %s',
                $this->table->sql($driver),
                Str::of(', ')->join($keys)->toString(),
                $this->row->sql($driver),
            );
        }

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
