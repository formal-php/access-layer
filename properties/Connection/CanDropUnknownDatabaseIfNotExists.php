<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\Query;
use Fixtures\Formal\AccessLayer\Table\Name;
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class CanDropUnknownDatabaseIfNotExists implements Property
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
        return 'Can drop unknown database if not exists';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        $rows = $connection(Query\DropTable::ifExists($this->name));

        Assert::assertCount(0, $rows);

        return $connection;
    }
}
