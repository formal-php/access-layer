<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Query\Constraint\PrimaryKey,
    Query\Constraint\ForeignKey,
    Row,
    Table\Name,
    Table\Column,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class CreateTable implements Query
{
    private Name $name;
    /** @var Sequence<Column> */
    private Sequence $columns;
    /** @var Sequence<PrimaryKey|ForeignKey> */
    private Sequence $constraints;
    private bool $ifNotExists;

    /**
     * @param Sequence<Column> $columns
     * @param Sequence<PrimaryKey|ForeignKey> $constraints
     */
    private function __construct(
        bool $ifNotExists,
        Name $name,
        Sequence $columns,
        Sequence $constraints,
    ) {
        $this->ifNotExists = $ifNotExists;
        $this->name = $name;
        $this->columns = $columns;
        $this->constraints = $constraints;
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     */
    public static function named(Name $name, Column $first, Column ...$rest): self
    {
        return new self(
            false,
            $name,
            Sequence::of($first, ...$rest),
            Sequence::of(),
        );
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     */
    public static function ifNotExists(Name $name, Column $first, Column ...$rest): self
    {
        return new self(
            true,
            $name,
            Sequence::of($first, ...$rest),
            Sequence::of(),
        );
    }

    public function primaryKey(Column\Name $column): self
    {
        return new self(
            $this->ifNotExists,
            $this->name,
            $this->columns,
            ($this->constraints)(PrimaryKey::on($column)),
        );
    }

    public function foreignKey(Column\Name $column, Name $target, Column\Name $reference): self
    {
        return new self(
            $this->ifNotExists,
            $this->name,
            $this->columns,
            ($this->constraints)(ForeignKey::of($column, $target, $reference)),
        );
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
            Str::of(', ')
                ->join($this->columns->map(static fn($column) => $column->sql()))
                ->toString(),
            $this->constraints->match(
                static fn($first, $rest) => ', '.Str::of(', ')
                    ->join(
                        Sequence::of($first)
                            ->append($rest)
                            ->map(static fn($constraint) => $constraint->sql()),
                    )
                    ->toString(),
                static fn() => '',
            ),
        );
    }

    public function lazy(): bool
    {
        return false;
    }
}
