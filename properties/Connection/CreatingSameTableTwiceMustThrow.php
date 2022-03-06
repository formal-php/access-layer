<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query,
    Exception\QueryFailed,
};
use Fixtures\Formal\AccessLayer\Table\{
    Name,
    Column,
};
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class CreatingSameTableTwiceMustThrow implements Property
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
        return Set\Property::of(
            self::class,
            Name::any(),
            Column::list(),
        );
    }

    public function name(): string
    {
        return 'Creating same table twice must throw';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        try {
            $expected = Query\CreateTable::named($this->name, ...$this->columns);
            $connection(Query\CreateTable::named($this->name, ...$this->columns));
            $connection($expected);
            Assert::fail('it should throw');
        } catch (QueryFailed $e) {
            Assert::assertSame($expected, $e->query());
        } finally {
            $connection(Query\DropTable::ifExists($this->name));
        }

        return $connection;
    }
}
