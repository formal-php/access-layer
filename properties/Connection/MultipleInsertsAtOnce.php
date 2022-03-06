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

final class MultipleInsertsAtOnce implements Property
{
    private string $uuid1;
    private string $username1;
    private int $number1;
    private string $uuid2;
    private string $username2;
    private int $number2;

    public function __construct(
        string $uuid1,
        string $username1,
        int $number1,
        string $uuid2,
        string $username2,
        int $number2,
    ) {
        $this->uuid1 = $uuid1;
        $this->username1 = $username1;
        $this->number1 = $number1;
        $this->uuid2 = $uuid2;
        $this->username2 = $username2;
        $this->number2 = $number2;
    }

    public static function any(): Set
    {
        return Set\Property::of(
            self::class,
            Set\Uuid::any(),
            Set\Strings::madeOf(Set\Chars::ascii())->between(0, 255),
            Set\Integers::any(),
            Set\Uuid::any(),
            Set\Strings::madeOf(Set\Chars::ascii())->between(0, 255),
            Set\Integers::any(),
        );
    }

    public function name(): string
    {
        return 'Multiple inserts at once';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        $select = SQL::of("SELECT * FROM `test` WHERE `id` IN ('{$this->uuid1}', '{$this->uuid2}')");
        $rows = $connection($select);

        Assert::assertCount(0, $rows);

        $sequence = $connection(Query\Insert::into(
            new Table\Name('test'),
            Row::of([
                'id' => $this->uuid1,
                'username' => $this->username1,
                'registerNumber' => $this->number1,
            ]),
            Row::of([
                'id' => $this->uuid2,
                'username' => $this->username2,
                'registerNumber' => $this->number2,
            ]),
        ));

        Assert::assertCount(0, $sequence);

        $rows = $connection($select);

        Assert::assertCount(2, $rows);
        Assert::assertContains(
            $rows
                ->first()
                ->flatMap(static fn($row) => $row->column('id'))
                ->match(
                    static fn($id) => $id,
                    static fn() => null,
                ),
            [$this->uuid1, $this->uuid2],
        );
        Assert::assertContains(
            $rows
                ->first()
                ->flatMap(static fn($row) => $row->column('username'))
                ->match(
                    static fn($username) => $username,
                    static fn() => null,
                ),
            [$this->username1, $this->username2],
        );
        Assert::assertContains(
            $rows
                ->first()
                ->flatMap(static fn($row) => $row->column('registerNumber'))
                ->match(
                    static fn($registerNumber) => $registerNumber,
                    static fn() => null,
                ),
            [$this->number1, $this->number2],
        );
        Assert::assertContains(
            $rows
                ->last()
                ->flatMap(static fn($row) => $row->column('id'))
                ->match(
                    static fn($id) => $id,
                    static fn() => null,
                ),
            [$this->uuid1, $this->uuid2],
        );
        Assert::assertContains(
            $rows
                ->last()
                ->flatMap(static fn($row) => $row->column('username'))
                ->match(
                    static fn($username) => $username,
                    static fn() => null,
                ),
            [$this->username1, $this->username2],
        );
        Assert::assertContains(
            $rows
                ->last()
                ->flatMap(static fn($row) => $row->column('registerNumber'))
                ->match(
                    static fn($registerNumber) => $registerNumber,
                    static fn() => null,
                ),
            [$this->number1, $this->number2],
        );

        return $connection;
    }
}
