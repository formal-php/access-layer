<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Table\Column\Name;

use Formal\AccessLayer\Table;

/**
 * @psalm-immutable
 */
final class Namespaced
{
    private Table\Column\Name $column;
    private Table\Name|Table\Name\Aliased $table;

    private function __construct(
        Table\Column\Name $column,
        Table\Name|Table\Name\Aliased $table,
    ) {
        $this->column = $column;
        $this->table = $table;
    }

    /**
     * @psalm-pure
     */
    public static function of(
        Table\Column\Name $column,
        Table\Name|Table\Name\Aliased $table,
    ): self {
        return new self($column, $table);
    }

    /**
     * @param non-empty-string $alias
     */
    public function as(string $alias): Aliased
    {
        return Aliased::of($this, $alias);
    }

    public function column(): Table\Column\Name
    {
        return $this->column;
    }

    public function table(): Table\Name|Table\Name\Aliased
    {
        return $this->table;
    }

    /**
     * @return non-empty-string
     */
    public function sql(): string
    {
        $table = match (true) {
            $this->table instanceof Table\Name\Aliased => "`{$this->table->alias()}`",
            default => $this->table->sql(),
        };

        return "$table.{$this->column->sql()}";
    }
}
