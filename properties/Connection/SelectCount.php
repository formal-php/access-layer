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
use Fixtures\Formal\AccessLayer\Table\Column\Name as FName;
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Connection>
 */
final class SelectCount implements Property
{
    private string $name;
    private string $uuid;
    private string $username;
    private int $number;

    public function __construct($name, string $uuid, string $username, int $number)
    {
        $this->name = $name->toString();
        $this->uuid = $uuid;
        $this->username = $username;
        $this->number = $number;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            FName::any(),
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

        $rows = $connection(Select::from(Name::of('test')));

        $assert
            ->expected($rows->size())
            ->same(
                $connection(Select::from(Name::of('test'))->count($this->name))
                    ->first()
                    ->flatMap(fn($row) => $row->column($this->name))
                    ->match(
                        static fn($size) => (int) $size,
                        static fn() => null,
                    ),
            );

        return $connection;
    }
}
