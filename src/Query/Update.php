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
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class Update implements Query
{
    private Name $table;
    private Row $row;
    private Where $where;

    private function __construct(Name $table, Row $row)
    {
        $this->table = $table;
        $this->row = $row;
        $this->where = Where::everything();
    }

    /**
     * @psalm-pure
     */
    public static function set(Name $table, Row $row): self
    {
        return new self($table, $row);
    }

    public function where(Specification $specification): self
    {
        $self = clone $this;
        $self->where = Where::of($specification);

        return $self;
    }

    public function parameters(): Sequence
    {
        return $this
            ->row
            ->values()
            ->map(static fn($value) => Parameter::of($value->value(), $value->type()))
            ->append($this->where->parameters());
    }

    public function sql(): string
    {
        /** @var Sequence<string> */
        $columns = $this
            ->row
            ->values()
            ->map(static fn($value) => "{$value->column()->sql()} = ?");

        /** @var non-empty-string */
        return \sprintf(
            'UPDATE %s SET %s %s',
            $this->table->sql(),
            Str::of(', ')->join($columns)->toString(),
            $this->where->sql(),
        );
    }

    public function lazy(): bool
    {
        return false;
    }
}
