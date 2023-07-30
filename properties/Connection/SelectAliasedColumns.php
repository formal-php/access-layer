<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Query\Parameter,
    Query\Select,
    Table\Name,
    Table\Column,
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
final class SelectAliasedColumns implements Property
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
            ->with(Parameter::of($this->uuid))
            ->with(Parameter::of($this->username))
            ->with(Parameter::of($this->number));
        $connection($insert);

        $select = Select::from($table = Name::of('test')->as('_test'))->columns(
            Column\Name::of('id')->in($table)->as('test_id'),
            Column\Name::of('id')->as('test_id_bis'),
        );
        $rows = $connection($select);

        $assert
            ->number($rows->size())
            ->greaterThanOrEqual(1);
        $assert->true($rows->first()->match(
            static fn($row) => $row->contains('test_id'),
            static fn() => null,
        ));
        $assert->true($rows->first()->match(
            static fn($row) => $row->contains('test_id_bis'),
            static fn() => null,
        ));
        $assert->false($rows->first()->match(
            static fn($row) => $row->contains('id'),
            static fn() => null,
        ));
        $assert->false($rows->first()->match(
            static fn($row) => $row->contains('username'),
            static fn() => null,
        ));
        $assert->false($rows->first()->match(
            static fn($row) => $row->contains('registerNumber'),
            static fn() => null,
        ));

        return $connection;
    }
}
