<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Row;

use Formal\AccessLayer\{
    Table\Column\Name,
    Query\Parameter\Type,
};

/**
 * @psalm-immutable
 */
final class Value
{
    private Name|Name\Namespaced $column;
    private mixed $value;
    private Type $type;

    public function __construct(Name|Name\Namespaced $column, mixed $value, Type $type = null)
    {
        $this->column = $column;
        $this->value = $value;
        $this->type = $type ?? Type::for($value);
    }

    public function column(): Name
    {
        return match (true) {
            $this->column instanceof Name => $this->column,
            default => $this->column->column(),
        };
    }

    /**
     * @return non-empty-string
     */
    public function columnSql(): string
    {
        return $this->column->sql();
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function type(): Type
    {
        return $this->type;
    }
}
