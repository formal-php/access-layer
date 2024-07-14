<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Table\Name,
    Driver,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Delete implements Query
{
    private Name|Name\Aliased $table;
    private Where $where;

    private function __construct(
        Name|Name\Aliased $table,
        Where $where,
    ) {
        $this->table = $table;
        $this->where = $where;
    }

    /**
     * @psalm-pure
     */
    public static function from(Name|Name\Aliased $table): self
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

    public function sql(Driver $driver): string
    {
        /** @var non-empty-string */
        return \sprintf(
            'DELETE %s FROM %s %s',
            match (true) {
                $driver === Driver::postgres => '',
                $this->table instanceof Name\Aliased => $driver->escapeName($this->table->alias()),
                default => $this->table->sql($driver),
            },
            $this->table->sql($driver),
            $this->where->sql($driver),
        );
    }

    public function lazy(): bool
    {
        return false;
    }
}
