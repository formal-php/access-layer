<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\Query\{
    SQL,
    Parameter,
};
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class ParametersCanBeBoundByName implements Property
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
        return 'Parameters can be bound by name';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        $insert = new SQL('INSERT INTO `test` VALUES (:uuid, :username, :registerNumber);');
        $insert = $insert
            ->with(Parameter::named('uuid', $this->uuid))
            ->with(Parameter::named('username', $this->username))
            ->with(Parameter::named('registerNumber', $this->number));
        $connection($insert);

        $rows = $connection(new SQL("SELECT * FROM `test` WHERE `id` = '{$this->uuid}'"));

        Assert::assertCount(1, $rows);
        Assert::assertSame($this->uuid, $rows->first()->column('id'));
        Assert::assertSame($this->username, $rows->first()->column('username'));
        Assert::assertSame((string) $this->number, $rows->first()->column('registerNumber'));

        return $connection;
    }
}
