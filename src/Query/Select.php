<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Query\Parameter,
    Row,
    Table\Name,
    Table\Column,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\Sequence;
use function Innmind\Immutable\join;

final class Select implements Query
{
    private Name $table;
    /** @var Sequence<Column\Name> */
    private Sequence $columns;
    private Where $where;

    public function __construct(Name $table)
    {
        $this->table = $table;
        $this->columns = Sequence::of(Column\Name::class);
        $this->where = Where::everything();
    }

    public function columns(Column\Name $first, Column\Name ...$rest): self
    {
        $self = clone $this;
        $self->columns = Sequence::of(Column\Name::class, $first, ...$rest);

        return $self;
    }

    public function where(Specification $specification): self
    {
        $self = clone $this;
        $self->where = Where::of($specification);

        return $self;
    }

    public function parameters(): Sequence
    {
        return $this->where->parameters();
    }

    public function sql(): string
    {
        return \sprintf(
            'SELECT %s FROM %s %s',
            $this->columns->empty() ? '*' : $this->buildColumns(),
            $this->table->sql(),
            $this->where->sql(),
        );
    }

    private function buildColumns(): string
    {
        $columns = $this->columns->mapTo(
            'string',
            static fn($column) => $column->sql(),
        );

        return join(', ', $columns)->toString();
    }
}
