<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Query,
    Table,
    Row,
};
use Innmind\Specification\{
    Comparator,
    Composable,
    Sign,
};
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class UpdateSpecificRow implements Property
{
    private string $uuid1;
    private string $uuid2;

    public function __construct(string $uuid1, string $uuid2)
    {
        $this->uuid1 = $uuid1;
        $this->uuid2 = $uuid2;
    }

    public static function any(): Set
    {
        return Set\Property::of(
            self::class,
            Set\Uuid::any(),
            Set\Uuid::any(),
        );
    }

    public function name(): string
    {
        return 'Update specific row';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        $connection(new Query\Insert(
            new Table\Name('test'),
            Row::of([
                'id' => $this->uuid1,
                'username' => 'foo',
                'registerNumber' => 42,
            ]),
            Row::of([
                'id' => $this->uuid2,
                'username' => 'foo',
                'registerNumber' => 42,
            ]),
        ));

        $update = new Query\Update(
            new Table\Name('test'),
            Row::of(['registerNumber' => 24]),
        );
        $update = $update->where(new class($this->uuid1) implements Comparator {
            use Composable;

            private string $uuid;

            public function __construct(string $uuid)
            {
                $this->uuid = $uuid;
            }

            public function property(): string
            {
                return 'id';
            }

            public function sign(): Sign
            {
                return Sign::equality;
            }

            public function value(): string
            {
                return $this->uuid;
            }
        });
        $sequence = $connection($update);

        Assert::assertCount(0, $sequence);

        $rows = $connection(new SQL("SELECT * FROM `test` WHERE `id` = '{$this->uuid1}'"));

        Assert::assertCount(1, $rows);
        Assert::assertSame(24, $rows->first()->match(
            static fn($row) => $row->column('registerNumber'),
            static fn() => null,
        ));

        $rows = $connection(new SQL("SELECT * FROM `test` WHERE `id` = '{$this->uuid2}'"));

        Assert::assertCount(1, $rows);
        Assert::assertSame(42, $rows->first()->match(
            static fn($row) => $row->column('registerNumber'),
            static fn() => null,
        ));

        return $connection;
    }
}
