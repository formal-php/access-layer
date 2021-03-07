<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Query\Parameter,
    Exception\QueryFailed,
};
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class MustThrowWhenValueDoesntFitTheSchema implements Property
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
        return "Must throw when value doesn't fit the schema";
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        try {
            $query = new SQL('INSERT INTO `test` VALUES (:uuid, :username, :registerNumber);');
            $query = $query
                ->with(Parameter::named('uuid', $this->uuid.$this->uuid)) // too long
                ->with(Parameter::named('username', $this->username))
                ->with(Parameter::named('registerNumber', $this->number));
            $connection($query);
            Assert::fail('it should throw an exception');
        } catch (QueryFailed $e) {
            Assert::assertSame($query, $e->query());
        }

        return $connection;
    }
}
