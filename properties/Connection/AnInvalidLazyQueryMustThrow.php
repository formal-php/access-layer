<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Exception\QueryFailed,
};
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class AnInvalidLazyQueryMustThrow implements Property
{
    public static function any(): Set
    {
        return Set\Property::of(self::class);
    }

    public function name(): string
    {
        return 'An invalid lazy query must throw';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        $query = SQL::onDemand('INSERT');
        $result = $connection($query);

        try {
            // throw only now because the force the execution of the sequence
            $result->toList();
            Assert::fail('it should throw an exception');
        } catch (QueryFailed $e) {
            Assert::assertSame($query, $e->query());
            Assert::assertIsInt($e->code());
            Assert::assertIsString($e->message());
        }

        return $connection;
    }
}
