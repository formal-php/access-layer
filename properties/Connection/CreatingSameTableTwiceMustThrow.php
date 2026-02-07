<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query,
    Exception\QueryFailed,
    Connection,
};
use Fixtures\Formal\AccessLayer\Table\{
    Name,
    Column,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Connection>
 */
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
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Name::any(),
            Column::list(),
        )->toSet();
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        try {
            $expected = Query\CreateTable::named($this->name, ...$this->columns);
            $connection(Query\CreateTable::named($this->name, ...$this->columns));
            $connection($expected);
            $assert->fail('it should throw');
        } catch (QueryFailed $e) {
            $assert->same($expected, $e->query());
        } finally {
            $connection(Query\DropTable::ifExists($this->name));
        }

        return $connection;
    }
}
