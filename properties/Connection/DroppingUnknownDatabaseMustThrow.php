<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query,
    Exception\QueryFailed,
};
use Fixtures\Formal\AccessLayer\Table\Name;
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class DroppingUnknownDatabaseMustThrow implements Property
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public static function any(): Set
    {
        return Set\Property::of(
            self::class,
            Name::any(),
        );
    }

    public function name(): string
    {
        return 'Dropping unknown database must throw';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        try {
            $query = Query\DropTable::named($this->name);
            $connection($query);
            Assert::fail('it should throw');
        } catch (QueryFailed $e) {
            Assert::assertSame($query, $e->query());
        }

        return $connection;
    }
}
