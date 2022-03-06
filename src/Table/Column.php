<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Table;

use Formal\AccessLayer\Table\Column\{
    Name,
    Type,
};

/**
 * @psalm-immutable
 */
final class Column
{
    private Name $name;
    private Type $type;

    public function __construct(Name $name, Type $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function type(): Type
    {
        return $this->type;
    }

    /**
     * @return non-empty-string
     */
    public function sql(): string
    {
        return "{$this->name->sql()} {$this->type->sql()}";
    }
}
