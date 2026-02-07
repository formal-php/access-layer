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
final class CreateTableIfNotExists implements Property
{
    private $name;
    private array $columns;

    public function __construct($name, array $columns)
    {
        $this->name = $name;
        $this->columns = $columns;
    }

    public static function any(): Set
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Name::any(),
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
            $_ = $connection(Query\CreateTable::named($this->name, ...$this->columns));
            $rows = $connection(Query\CreateTable::ifNotExists($this->name, ...$this->columns));

            $assert->same(0, $rows->size());
        } finally {
            $_ = $connection(Query\DropTable::ifExists($this->name));
        }

        return $connection;
    }
}
