<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\Query\SQL;
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class AllowToStartTwoQueriesInParallel implements Property
{
    public static function any(): Set
    {
        return Set\Property::of(self::class);
    }

    public function name(): string
    {
        return 'Allow to start 2 queries in parallel';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        $result1 = $connection(new SQL('show tables'));
        $result2 = $connection(new SQL('show tables'));

        // by using any() we only do a partial iteration over the results
        Assert::assertTrue($result1->any(static fn() => true));
        Assert::assertTrue($result2->any(static fn() => true));

        return $connection;
    }
}
