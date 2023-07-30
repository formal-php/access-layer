<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\Rollback,
    Exception\QueryFailed,
    Connection,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Connection>
 */
final class RollbackingAnUnstartedTransactionMustThrow implements Property
{
    public static function any(): Set
    {
        return Set\Elements::of(new self);
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        try {
            $query = new Rollback;
            $connection($query);
            $assert->fail('it should throw an exception');
        } catch (QueryFailed $e) {
            $assert->same($query, $e->query());
        }

        return $connection;
    }
}
