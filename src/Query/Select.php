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
    private bool $lazy;
    /** @var Sequence<Column\Name> */
    private Sequence $columns;
    private Where $where;

    private function __construct(Name $table, bool $lazy)
    {
        $this->table = $table;
        $this->lazy = $lazy;
        /** @var Sequence<Column\Name> */
        $this->columns = Sequence::of();
        $this->where = Where::everything();
    }

    /**
     * @psalm-pure
     */
    public static function of(Name $table): self
    {
        return new self($table, false);
    }

    /**
     * @psalm-pure
     */
    public static function onDemand(Name $table): self
    {
        return new self($table, true);
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

    public function lazy(): bool
    {
        return $this->lazy;
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
