<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query,
    Table\Column as ConcreteColumn,
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

final class CreateTableWithForeignKey implements Property
{
    private $name1;
    private $name2;
    private $primaryKey;
    private $foreignKey;

    public function __construct($names, $primaryKey, $foreignKey)
    {
        $this->name1 = $names[0];
        $this->name2 = $names[1];
        $this->primaryKey = $primaryKey;
        $this->foreignKey = $foreignKey;
    }

    public static function any(): Set
    {
        // max length of 30 for column names as combined can't be higher than 64
        // as it's the limit of the created constraint name
        return Set\Property::of(
            self::class,
            Name::pair(),
            Column::any(Column\Type::constraint(), 30),
            Column::any(null, 30),
        );
    }

    public function name(): string
    {
        return 'Create table with foreign key';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        try {
            $create = new Query\CreateTable($this->name1, $this->primaryKey);
            $create = $create->primaryKey($this->primaryKey->name());
            $rows = $connection($create);

            Assert::assertCount(0, $rows);

            $create = new Query\CreateTable($this->name2, new ConcreteColumn(
                $this->foreignKey->name(),
                $this->primaryKey->type(),
            ));
            $create = $create->foreignKey(
                $this->foreignKey->name(),
                $this->name1,
                $this->primaryKey->name(),
            );
            $rows = $connection($create);

            Assert::assertCount(0, $rows);
        } finally {
            $connection(Query\DropTable::ifExists($this->name2));
            $connection(Query\DropTable::ifExists($this->name1));
        }

        return $connection;
    }
}
