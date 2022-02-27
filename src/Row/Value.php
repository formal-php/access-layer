<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Row;

use Formal\AccessLayer\{
    Table\Column\Name,
    Query\Parameter\Type,
};

final class Value
{
    private Name $column;
    private mixed $value;
    private Type $type;

    public function __construct(Name $column, mixed $value, Type $type = null)
    {
        $this->column = $column;
        $this->value = $value;
        $this->type = $type ?? Type::unspecified;
    }

    public function column(): Name
    {
        return $this->column;
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
