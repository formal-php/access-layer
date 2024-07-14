<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Row;

use Formal\AccessLayer\{
    Table\Column\Name,
    Query\Parameter\Type,
    Driver,
};

/**
 * @psalm-immutable
 */
final class Value
{
    private Name|Name\Namespaced $column;
    private mixed $value;
    private Type $type;

    private function __construct(Name|Name\Namespaced $column, mixed $value, Type $type = null)
    {
        $this->column = $column;
        $this->value = $value;
        $this->type = $type ?? Type::for($value);
    }

    /**
     * @psalm-pure
     */
    public static function of(
        Name|Name\Namespaced $column,
        mixed $value,
        Type $type = null,
    ): self {
        return new self($column, $value, $type);
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
    public function columnSql(Driver $driver): string
    {
        return $this->column->sql($driver);
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
