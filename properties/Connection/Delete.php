<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Query,
    Table,
    Row,
};
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class Delete implements Property
{
    private string $uuid;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public static function any(): Set
    {
        return Set\Property::of(
            self::class,
            Set\Uuid::any(),
        );
    }

    public function name(): string
    {
        return 'Delete';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        $select = SQL::of('SELECT * FROM `test`');
        $connection(Query\Insert::into(
            new Table\Name('test'),
            Row::of([
                'id' => $this->uuid,
                'username' => 'foo',
                'registerNumber' => 42,
            ]),
        ));

        $sequence = $connection(Query\Delete::from(new Table\Name('test')));

        Assert::assertCount(0, $sequence);

        $rows = $connection($select);

        Assert::assertCount(0, $rows);

        return $connection;
    }
}
