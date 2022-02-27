<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\Query\{
    SQL,
    Parameter,
    StartTransaction,
    Commit,
};
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class ContentIsAccessibleAfterCommit implements Property
{
    private string $uuid;
    private string $username;
    private int $number;

    public function __construct(string $uuid, string $username, int $number)
    {
        $this->uuid = $uuid;
        $this->username = $username;
        $this->number = $number;
    }

    public static function any(): Set
    {
        return Set\Property::of(
            self::class,
            Set\Uuid::any(),
            Set\Strings::madeOf(Set\Chars::ascii())->between(0, 255),
            Set\Integers::any(),
        );
    }

    public function name(): string
    {
        return 'Content is accessible after commit';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        $connection(new StartTransaction);

        $insert = new SQL('INSERT INTO `test` VALUES (?, ?, ?);');
        $insert = $insert
            ->with(Parameter::of($this->uuid))
            ->with(Parameter::of($this->username))
            ->with(Parameter::of($this->number));
        $connection($insert);

        $connection(new Commit);

        $rows = $connection(new SQL("SELECT * FROM `test` WHERE `id` = '{$this->uuid}'"));

        Assert::assertCount(1, $rows);
        Assert::assertSame($this->uuid, $rows->first()->match(
            static fn($row) => $row->column('id'),
            static fn() => null,
        ));
        Assert::assertSame($this->username, $rows->first()->match(
            static fn($row) => $row->column('username'),
            static fn() => null,
        ));
        Assert::assertSame($this->number, $rows->first()->match(
            static fn($row) => $row->column('registerNumber'),
            static fn() => null,
        ));

        return $connection;
    }
}
