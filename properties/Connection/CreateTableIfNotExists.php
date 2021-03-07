<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\Query;
use Fixtures\Formal\AccessLayer\Table\{
    Name,
    Column,
};
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class CreateTableIfNotExists implements Property
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
        return 'Create table if not exists';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        try {
            $connection(new Query\CreateTable($this->name, ...$this->columns));
            $rows = $connection(Query\CreateTable::ifNotExists($this->name, ...$this->columns));

            Assert::assertCount(0, $rows);
        } finally {
            $connection(Query\DropTable::ifExists($this->name));
        }

        return $connection;
    }
}
