<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Table\Name,
    Row,
    Driver,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class Update implements Builder
{
    private Name|Name\Aliased $table;
    private Row $row;
    private Where $where;

    private function __construct(
        Name|Name\Aliased $table,
        Row $row,
        Where $where,
    ) {
        $this->table = $table;
        $this->row = $row;
        $this->where = $where;
    }

    /**
     * @psalm-pure
     */
    public static function set(Name|Name\Aliased $table, Row $row): self
    {
        return new self($table, $row, Where::everything());
    }

    public function where(Specification $specification): self
    {
        return new self(
            $this->table,
            $this->row,
            Where::of($specification),
        );
    }

    #[\Override]
    public function normalize(Driver $driver): Query
    {
        [$where, $parameters] = $this->where->normalize($driver);
        /** @var Sequence<string> */
        $columns = $this
            ->row
            ->values()
            ->map(static fn($value) => "{$value->columnSql($driver)} = ?");
        $parameters = $this
            ->row
            ->values()
            ->map(static fn($value) => Parameter::of($value->value(), $value->type()))
            ->append($parameters);

        return Query::of(
            \sprintf(
                'UPDATE %s SET %s %s',
                $this->table->sql($driver),
                Str::of(', ')->join($columns)->toString(),
                $where,
            ),
            $parameters,
        );
    }
}
