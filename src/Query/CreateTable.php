<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Row,
    Table\Name,
    Table\Column,
};
use Innmind\Immutable\Sequence;

final class CreateTable implements Query
{
    private Name $name;
    /** @var non-empty-list<Column> */
    private array $columns;
    /** @var list<string> */
    private array $constraints = [];
    private bool $ifNotExists = false;

    /**
     * @no-named-arguments
     */
    public function __construct(Name $name, Column $first, Column ...$rest)
    {
        $this->name = $name;
        $this->columns = [$first, ...$rest];
    }

    /**
     * @no-named-arguments
     */
    public static function ifNotExists(Name $name, Column $first, Column ...$rest): self
    {
        $self = new self($name, $first, ...$rest);
        $self->ifNotExists = true;

        return $self;
    }

    public function primaryKey(Column\Name $column): self
    {
        $self = clone $this;
        $self->constraints[] = "PRIMARY KEY ({$column->sql()})";

        return $self;
    }

    public function foreignKey(Column\Name $column, Name $target, Column\Name $reference): self
    {
        $self = clone $this;
        $self->constraints[] = \sprintf(
            'CONSTRAINT `FK_%s_%s` FOREIGN KEY (%s) REFERENCES %s(%s)',
            $column->toString(),
            $reference->toString(),
            $column->sql(),
            $target->sql(),
            $reference->sql(),
        );

        return $self;
    }

    public function parameters(): Sequence
    {
        return Sequence::of(Row::class);
    }

    public function sql(): string
    {
        return \sprintf(
            'CREATE TABLE %s %s (%s%s)',
            $this->ifNotExists ? 'IF NOT EXISTS' : '',
            $this->name->sql(),
            \implode(
                ', ',
                \array_map(static fn($column) => $column->sql(), $this->columns),
            ),
            \count($this->constraints) > 0 ? ', '.\implode(', ', $this->constraints) : '',
        );
    }
}
