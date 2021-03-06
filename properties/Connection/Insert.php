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

final class Insert implements Property
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
        return 'Insert';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        $select = new SQL("SELECT * FROM `test` WHERE `id` = '{$this->uuid}'");
        $rows = $connection($select);

        Assert::assertCount(0, $rows);

        $sequence = $connection(new Query\Insert(
            new Table\Name('test'),
            Row::of([
                'id' => $this->uuid,
                'username' => 'foo',
                'registerNumber' => 42,
            ]),
        ));

        Assert::assertCount(0, $sequence);

        $rows = $connection($select);

        Assert::assertCount(1, $rows);
        Assert::assertSame($this->uuid, $rows->first()->column('id'));
        Assert::assertSame('foo', $rows->first()->column('username'));
        Assert::assertSame('42', $rows->first()->column('registerNumber'));

        return $connection;
    }
}
