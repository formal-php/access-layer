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
use Innmind\Immutable\Sequence;
use function Innmind\Immutable\join;

final class Insert implements Query
{
    private Name $table;
    /** @var Sequence<Row> */
    private Sequence $rows;

    public function __construct(Name $table, Row $first, Row ...$rest)
    {
        $this->table = $table;
        $this->rows = Sequence::of(Row::class, $first, ...$rest);
    }

    public function parameters(): Sequence
    {
        /** @var Sequence<Parameter> */
        return $this->rows->reduce(
            Sequence::of(Parameter::class),
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
        $inserts = $this->rows->mapTo(
            'string',
            fn($row) => $this->buildInsert($row),
        );

        return join('; ', $inserts)->toString();
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
