<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Query\Constraint\PrimaryKey,
    Query\Constraint\ForeignKey,
    Query\Constraint\Unique,
    Table\Name,
    Table\Column,
    Driver,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class CreateTable implements Builder
{
    private Name $name;
    /** @var Sequence<Column> */
    private Sequence $columns;
    /** @var Sequence<PrimaryKey|ForeignKey|Unique> */
    private Sequence $constraints;
    private bool $ifNotExists;

    /**
     * @param Sequence<Column> $columns
     * @param Sequence<PrimaryKey|ForeignKey|Unique> $constraints
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
        return $this->constraint(PrimaryKey::on($column));
    }

    public function foreignKey(Column\Name $column, Name $target, Column\Name $reference): self
    {
        return $this->constraint(ForeignKey::of($column, $target, $reference));
    }

    /**
     * @no-named-arguments
     */
    public function unique(Column\Name $column, Column\Name ...$columns): self
    {
        return $this->constraint(Unique::of($column, ...$columns));
    }

    public function constraint(PrimaryKey|ForeignKey|Unique $constraint): self
    {
        return new self(
            $this->ifNotExists,
            $this->name,
            $this->columns,
            ($this->constraints)($constraint),
        );
    }

    #[\Override]
    public function normalize(Driver $driver): Query
    {
        return SQL::of(\sprintf(
            'CREATE TABLE %s %s (%s%s)',
            $this->ifNotExists ? 'IF NOT EXISTS' : '',
            $this->name->sql($driver),
            Str::of(', ')
                ->join($this->columns->map(static fn($column) => $column->sql($driver)))
                ->toString(),
            $this->constraints->match(
                static fn($first, $rest) => ', '.Str::of(', ')
                    ->join(
                        Sequence::of($first)
                            ->append($rest)
                            ->map(static fn($constraint) => $constraint->sql($driver)),
                    )
                    ->toString(),
                static fn() => '',
            ),
        ));
    }
}
