<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
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
final class AllowToStartTwoQueriesInParallel implements Property
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
        $result1 = $connection(SQL::of('show tables'));
        $result2 = $connection(SQL::of('show tables'));

        // by using any() we only do a partial iteration over the results
        $assert->true($result1->any(static fn() => true));
        $assert->true($result2->any(static fn() => true));

        return $connection;
    }
}
