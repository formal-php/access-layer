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

/**
 * @psalm-immutable
 */
final class Delete implements Query
{
    private Name $table;
    private Where $where;

    private function __construct(Name $table, Where $where)
    {
        $this->table = $table;
        $this->where = $where;
    }

    /**
     * @psalm-pure
     */
    public static function from(Name $table): self
    {
        return new self($table, Where::everything());
    }

    public function where(Specification $specification): self
    {
        return new self(
            $this->table,
            Where::of($specification),
        );
    }

    public function parameters(): Sequence
    {
        return $this->where->parameters();
    }

    public function sql(): string
    {
        /** @var non-empty-string */
        return \sprintf(
            'DELETE FROM %s %s',
            $this->table->sql(),
            $this->where->sql(),
        );
    }

    public function lazy(): bool
    {
        return false;
    }
}
