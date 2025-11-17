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
final class Insert implements Builder
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
    public function normalize(Driver $driver): Query
    {
        if ($this->row instanceof Select) {
            $columns = $this->row->names();
            $query = $this->row->normalize($driver);

            if ($columns->empty()) {
                throw new \LogicException('You need to specify the columns to select when inserting');
            }

            $keys = $columns->map(static fn($column) => $column->sql($driver));

            return SQL::of(
                \sprintf(
                    'INSERT INTO %s (%s) %s',
                    $this->table->sql($driver),
                    Str::of(', ')->join($keys)->toString(),
                    $query->sql($driver),
                ),
                $query->parameters(),
            );
        }

        /** @var Sequence<string> */
        $keys = $this->row->values()->map(static fn($value) => $value->columnSql($driver));
        /** @var Sequence<string> */
        $values = $this->row->values()->map(static fn() => '?');

        return SQL::of(
            \sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $this->table->sql($driver),
                Str::of(', ')->join($keys)->toString(),
                Str::of(', ')->join($values)->toString(),
            ),
            $this->row->values()->map(
                static fn($value) => Parameter::of(
                    $value->value(),
                    $value->type(),
                ),
            ),
        );
    }
}
