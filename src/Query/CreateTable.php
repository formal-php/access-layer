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

/**
 * @psalm-immutable
 */
final class CreateTable implements Query
{
    private Name $name;
    /** @var non-empty-list<Column> */
    private array $columns;
    /** @var list<string> */
    private array $constraints = [];
    private bool $ifNotExists;

    /**
     * @no-named-arguments
     */
    private function __construct(
        bool $ifNotExists,
        Name $name,
        Column $first,
        Column ...$rest,
    ) {
        $this->ifNotExists = $ifNotExists;
        $this->name = $name;
        $this->columns = [$first, ...$rest];
    }

    /**
     * @no-named-arguments
     */
    public static function named(Name $name, Column $first, Column ...$rest): self
    {
        return new self(false, $name, $first, ...$rest);
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     */
    public static function ifNotExists(Name $name, Column $first, Column ...$rest): self
    {
        return new self(true, $name, $first, ...$rest);
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
        /** @var Sequence<Query\Parameter> */
        return Sequence::of();
    }

    public function sql(): string
    {
        /** @var non-empty-string */
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

    public function lazy(): bool
    {
        return false;
    }
}
