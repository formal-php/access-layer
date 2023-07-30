<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Query\Parameter,
    Query\Parameter\Type,
    Connection,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Connection>
 */
final class ParameterTypesCanBeSpecified implements Property
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
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            Set\Uuid::any(),
            Set\Strings::madeOf(Set\Chars::ascii())->between(0, 255),
            Set\Integers::any(),
        );
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        $insert = SQL::of('INSERT INTO `test` VALUES (?, ?, ?);')
            ->with(Parameter::of($this->uuid, Type::string))
            ->with(Parameter::of($this->username, Type::string))
            ->with(Parameter::of($this->number, Type::int));
        $connection($insert);

        $rows = $connection(SQL::of("SELECT * FROM `test` WHERE `id` = '{$this->uuid}'"));

        $assert->count(1, $rows);
        $assert->same(
            $this->uuid,
            $rows
                ->first()
                ->flatMap(static fn($row) => $row->column('id'))
                ->match(
                    static fn($id) => $id,
                    static fn() => null,
                ),
        );
        $assert->same(
            $this->username,
            $rows
                ->first()
                ->flatMap(static fn($row) => $row->column('username'))
                ->match(
                    static fn($username) => $username,
                    static fn() => null,
                ),
        );
        $assert->same(
            $this->number,
            $rows
                ->first()
                ->flatMap(static fn($row) => $row->column('registerNumber'))
                ->match(
                    static fn($registerNumber) => $registerNumber,
                    static fn() => null,
                ),
        );

        return $connection;
    }
}
