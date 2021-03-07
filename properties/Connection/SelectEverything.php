<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Query\Parameter,
    Query\Select,
    Table\Name,
};
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class SelectEverything implements Property
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
        return 'Select everything';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        $insert = new SQL('INSERT INTO `test` VALUES (?, ?, ?);');
        $insert = $insert
            ->with(Parameter::of($this->uuid))
            ->with(Parameter::of($this->username))
            ->with(Parameter::of($this->number));
        $connection($insert);

        $rows = $connection(new Select(new Name('test')));

        Assert::assertGreaterThanOrEqual(1, $rows->size());
        Assert::assertTrue($rows->first()->contains('id'));
        Assert::assertTrue($rows->first()->contains('username'));
        Assert::assertTrue($rows->first()->contains('registerNumber'));

        return $connection;
    }
}
