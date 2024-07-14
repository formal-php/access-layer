<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Table;

use Formal\AccessLayer\{
    Table\Column\Name,
    Table\Column\Type,
    Driver,
};

/**
 * @psalm-immutable
 */
final class Column
{
    private Name $name;
    private Type $type;

    private function __construct(Name $name, Type $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @psalm-pure
     */
    public static function of(Name $name, Type $type): self
    {
        return new self($name, $type);
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
    public function sql(Driver $driver): string
    {
        return "{$this->name->sql($driver)} {$this->type->sql($driver)}";
    }
}
