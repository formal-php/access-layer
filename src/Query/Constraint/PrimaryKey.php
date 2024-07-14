<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query\Constraint;

use Formal\AccessLayer\{
    Table\Column,
    Driver,
};

/**
 * @psalm-immutable
 */
final class PrimaryKey
{
    private Column\Name $column;

    private function __construct(Column\Name $column)
    {
        $this->column = $column;
    }

    /**
     * @psalm-pure
     */
    public static function on(Column\Name $column): self
    {
        return new self($column);
    }

    /**
     * @return non-empty-string
     */
    public function sql(Driver $driver): string
    {
        return "PRIMARY KEY ({$this->column->sql($driver)})";
    }
}
