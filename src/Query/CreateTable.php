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
    private bool $ifNotExists = false;

    public function __construct(Name $name, Column $first, Column ...$rest)
    {
        $this->name = $name;
        $this->columns = [$first, ...$rest];
    }

    public static function ifNotExists(Name $name, Column $first, Column ...$rest): self
    {
        $self = new self($name, $first, ...$rest);
        $self->ifNotExists = true;

        return $self;
    }

    public function parameters(): Sequence
    {
        return Sequence::of(Row::class);
    }

    public function sql(): string
    {
        return \sprintf(
            'CREATE TABLE %s %s (%s)',
            $this->ifNotExists ? 'IF NOT EXISTS' : '',
            $this->name->sql(),
            \implode(
                ', ',
                \array_map(static fn($column) => $column->sql(), $this->columns),
            ),
        );
    }
}
