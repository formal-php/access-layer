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
use Innmind\Specification\Specification;
use Innmind\Immutable\Sequence;

final class Update implements Query
{
    private Name $table;
    private Row $row;
    private Where $where;

    public function __construct(Name $table, Row $row)
    {
        $this->table = $table;
        $this->row = $row;
        $this->where = Where::everything();
    }

    public function where(Specification $specification): self
    {
        $self = clone $this;
        $self->where = Where::of($specification);

        return $self;
    }

    public function parameters(): Sequence
    {
        /** @var Sequence<Parameter> */
        $parameters = $this->row->reduce(
            Sequence::of(),
            static function(Sequence $parameters, Column\Name $_, mixed $value, Type $type): Sequence {
                return ($parameters)(Parameter::of($value, $type));
            },
        );

        return $parameters->append($this->where->parameters());
    }

    public function sql(): string
    {
        /** @var list<string> $columns */
        $columns = $this->row->reduce(
            [],
            static function(array $columns, Column\Name $column, mixed $_): array {
                /** @psalm-suppress MixedArrayAssignment */
                $columns[] = "{$column->sql()} = ?";

                return $columns;
            },
        );

        return \sprintf(
            'UPDATE %s SET %s %s',
            $this->table->sql(),
            \implode(', ', $columns),
            $this->where->sql(),
        );
    }
}
