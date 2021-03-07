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

final class CreateTableWithPrimaryKey implements Property
{
    private $name;
    private $primaryKey;
    private array $columns;

    public function __construct($name, $primaryKey, array $columns)
    {
        $this->name = $name;
        $this->primaryKey = $primaryKey;
        $this->columns = \array_filter(
            $columns,
            static fn($column) => \strtolower($column->name()->toString()) !== \strtolower($primaryKey->name()->toString()),
        );
    }

    public static function any(): Set
    {
        return Set\Property::of(
            self::class,
            Name::any(),
            Column::any(Column\Type::constraint()),
            Column::list(),
        );
    }

    public function name(): string
    {
        return 'Create table with primary key';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        try {
            $create = new Query\CreateTable($this->name, $this->primaryKey, ...$this->columns);
            $create = $create->primaryKey($this->primaryKey->name());
            $rows = $connection($create);

            Assert::assertCount(0, $rows);
        } finally {
            $connection(Query\DropTable::ifExists($this->name));
        }

        return $connection;
    }
}
