<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query\Select;

use Formal\AccessLayer\{
    Table,
    Table\Column,
    Driver,
};
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class Join
{
    private Table\Name|Table\Name\Aliased $table;
    /**
     * @var ?array{
     *      Column\Name|Column\Name\Namespaced|Column\Name\Aliased,
     *      Column\Name|Column\Name\Namespaced|Column\Name\Aliased,
     * }
     */
    private ?array $on;

    /**
     * @param ?array{
     *      Column\Name|Column\Name\Namespaced|Column\Name\Aliased,
     *      Column\Name|Column\Name\Namespaced|Column\Name\Aliased,
     * } $on
     */
    private function __construct(
        Table\Name|Table\Name\Aliased $table,
        ?array $on = null,
    ) {
        $this->table = $table;
        $this->on = $on;
    }

    /**
     * @psalm-pure
     */
    public static function left(Table\Name|Table\Name\Aliased $table): self
    {
        return new self($table);
    }

    public function on(
        Column\Name|Column\Name\Namespaced|Column\Name\Aliased $left,
        Column\Name|Column\Name\Namespaced|Column\Name\Aliased $right,
    ): self {
        return new self(
            $this->table,
            [$left, $right],
        );
    }

    public function table(): Table\Name|Table\Name\Aliased
    {
        return $this->table;
    }

    /**
     * @return Maybe<array{
     *      Column\Name|Column\Name\Namespaced|Column\Name\Aliased,
     *      Column\Name|Column\Name\Namespaced|Column\Name\Aliased,
     * }>
     */
    public function condition(): Maybe
    {
        return Maybe::of($this->on);
    }

    /**
     * @return non-empty-string
     */
    public function sql(Driver $driver): string
    {
        $sql = ' LEFT JOIN '.$this->table->sql($driver);

        if (\is_array($this->on)) {
            [$left, $right] = $this->on;

            $sql .= \sprintf(
                ' ON %s = %s',
                $left->sql($driver),
                $right->sql($driver),
            );
        }

        return $sql;
    }
}
