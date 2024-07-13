<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\Insert,
    Query\Select,
    Table\Name,
    Row,
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
        Insert::into(
            new Name('test'),
            Row::of([
                'id' => $this->uuid,
                'username' => $this->username,
                'registerNumber' => $this->number,
            ]),
        )->foreach($connection);

        $rows = $connection(Select::from(new Name('test')));

        $assert
            ->number($rows->size())
            ->greaterThanOrEqual(1);
        $assert->true($rows->first()->match(
            static fn($row) => $row->contains('id'),
            static fn() => null,
        ));
        $assert->true($rows->first()->match(
            static fn($row) => $row->contains('username'),
            static fn() => null,
        ));
        $assert->true($rows->first()->match(
            static fn($row) => $row->contains('registerNumber'),
            static fn() => null,
        ));

        return $connection;
    }
}
