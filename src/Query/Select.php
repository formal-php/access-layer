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
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class Select implements Query
{
    private Name $table;
    /** @var Sequence<Column\Name> */
    private Sequence $columns;
    private Where $where;

    public function __construct(Name $table)
    {
        $this->table = $table;
        /** @var Sequence<Column\Name> */
        $this->columns = Sequence::of();
        $this->where = Where::everything();
    }

    /**
     * @no-named-arguments
     */
    public function columns(Column\Name $first, Column\Name ...$rest): self
    {
        $self = clone $this;
        $self->columns = Sequence::of($first, ...$rest);

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
        /** @var non-empty-string */
        return \sprintf(
            'SELECT %s FROM %s %s',
            $this->columns->empty() ? '*' : $this->buildColumns(),
            $this->table->sql(),
            $this->where->sql(),
        );
    }

    private function buildColumns(): string
    {
        $columns = $this->columns->map(
            static fn($column) => $column->sql(),
        );

        /** @psalm-suppress InvalidArgument Because non-empty-string instead of string */
        return Str::of(', ')->join($columns)->toString();
    }
}
