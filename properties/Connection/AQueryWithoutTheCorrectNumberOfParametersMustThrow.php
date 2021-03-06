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

final class AQueryWithoutTheCorrectNumberOfParametersMustThrow implements Property
{
    public static function any(): Set
    {
        return Set\Property::of(self::class);
    }

    public function name(): string
    {
        return 'A query without the correct number of parameters must throw';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        try {
            $query = new SQL('INSERT INTO `test` VALUES (:uuid, :username, :registerNumber);');
            $connection($query);
            Assert::fail('it should throw an exception');
        } catch (QueryFailed $e) {
            Assert::assertSame($query, $e->query());
            Assert::assertIsInt($e->code());
            Assert::assertIsString($e->message());
        }

        return $connection;
    }
}
