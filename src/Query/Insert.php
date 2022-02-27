<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Query\Parameter,
    Query\Parameter\Type,
    Table\Name,
    Table\Column,
    Row,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};

final class Insert implements Query
{
    private Name $table;
    /** @var Sequence<Row> */
    private Sequence $rows;

    /**
     * @no-named-arguments
     */
    public function __construct(Name $table, Row $first, Row ...$rest)
    {
        $this->table = $table;
        $this->rows = Sequence::of($first, ...$rest);
    }

    public function parameters(): Sequence
    {
        /** @var Sequence<Parameter> */
        return $this->rows->reduce(
            Sequence::of(),
            static function(Sequence $parameters, Row $row): Sequence {
                return $row->reduce(
                    $parameters,
                    static function(Sequence $parameters, Column\Name $_, mixed $value, Type $type): Sequence {
                        return ($parameters)(Parameter::of($value, $type));
                    },
                );
            },
        );
    }

    public function sql(): string
    {
        $inserts = $this->rows->map(
            fn($row) => $this->buildInsert($row),
        );

        return Str::of('; ')->join($inserts)->toString();
    }

    private function buildInsert(Row $row): string
    {
        /**
         * @var list<string> $keys
         * @var list<string> $values
         */
        ['keys' => $keys, 'values' => $values] = $row->reduce(
            ['keys' => [], 'values' => []],
            static function(array $row, Column\Name $column, mixed $_): array {
                /** @psalm-suppress MixedArrayAssignment */
                $row['keys'][] = $column->sql();
                /** @psalm-suppress MixedArrayAssignment */
                $row['values'][] = '?';

                return $row;
            },
        );

        return \sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table->sql(),
            \implode(', ', $keys),
            \implode(', ', $values),
        );
    }
}
