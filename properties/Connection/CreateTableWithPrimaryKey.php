<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query,
    Connection,
};
use Fixtures\Formal\AccessLayer\Table\{
    Name,
    Column,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Connection>
 */
final class CreateTableWithPrimaryKey implements Property
{
    private $name;
    private $primaryKey;
    private array $columns;

    public function __construct($name, $primaryKey, array $columns)
    {
        $this->name = $name;
        $this->primaryKey = $primaryKey;
        $this->columns = \array_filter(
            $columns,
            static fn($column) => \strtolower($column->name()->toString()) !== \strtolower($primaryKey->name()->toString()),
        );
    }

    public static function any(): Set
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Name::any(),
            Column::any(Column\Type::constraint()),
            Column::list(),
        )->toSet();
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        try {
            $create = Query\CreateTable::named($this->name, $this->primaryKey, ...$this->columns);
            $create = $create->primaryKey($this->primaryKey->name());
            $rows = $connection($create);

            $assert->count(0, $rows);
        } finally {
            $connection(Query\DropTable::ifExists($this->name));
        }

        return $connection;
    }
}
