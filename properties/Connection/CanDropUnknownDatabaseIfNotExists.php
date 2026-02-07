<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query,
    Connection,
};
use Fixtures\Formal\AccessLayer\Table\Name;
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Connection>
 */
final class CanDropUnknownDatabaseIfNotExists implements Property
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public static function any(): Set
    {
        return Name::any()->map(static fn($name) => new self($name));
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        $rows = $connection(Query\DropTable::ifExists($this->name));

        $assert->same(0, $rows->size());

        return $connection;
    }
}
