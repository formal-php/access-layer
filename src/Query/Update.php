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

final class Update implements Query
{
    private Name $table;
    private Row $row;

    public function __construct(Name $table, Row $row)
    {
        $this->table = $table;
        $this->row = $row;
    }

    public function parameters(): Sequence
    {
        /** @var Sequence<Parameter> */
        return $this->row->reduce(
            Sequence::of(Parameter::class),
            static function(Sequence $parameters, Column\Name $_, mixed $value, Type $type): Sequence {
                return ($parameters)(Parameter::of($value, $type));
            },
        );
    }

    public function sql(): string
    {
        /** @var list<string> $columns */
        $columns = $this->row->reduce(
            [],
            static function(array $columns, Column\Name $column, mixed $value): array {
                /** @psalm-suppress MixedArrayAssignment */
                $columns[] = "{$column->sql()} = ?";

                return $columns;
            },
        );

        return \sprintf(
            'UPDATE %s SET %s',
            $this->table->sql(),
            \implode(', ', $columns),
        );
    }
}
