<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query,
    Exception\QueryFailed,
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
final class DroppingUnknownDatabaseMustThrow implements Property
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
        try {
            $query = Query\DropTable::named($this->name);
            $connection($query);
            $assert->fail('it should throw');
        } catch (QueryFailed $e) {
            $assert->same($query, $e->query());
        }

        return $connection;
    }
}
