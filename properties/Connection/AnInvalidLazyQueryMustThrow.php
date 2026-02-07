<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
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
final class AnInvalidLazyQueryMustThrow implements Property
{
    public static function any(): Set
    {
        return Set::of(new self);
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        $query = SQL::onDemand('INSERT');
        $result = $connection($query);

        try {
            // throw only now because the force the execution of the sequence
            $_ = $result->toList();
            $assert->fail('it should throw an exception');
        } catch (QueryFailed $e) {
            $assert->same($query, $e->query());
            $assert->number($e->code())->int();
            $assert->string($e->message());
        }

        return $connection;
    }
}
