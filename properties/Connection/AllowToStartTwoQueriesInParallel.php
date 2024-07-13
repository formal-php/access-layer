<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Query\Insert,
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
final class AllowToStartTwoQueriesInParallel implements Property
{
    private string $uuid;
    private string $name;
    private int $number;

    private function __construct(string $uuid, string $name, int $number)
    {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->number = $number;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            Set\Uuid::any(),
            Set\Strings::madeOf(Set\Chars::ascii())->between(0, 125),
            Set\Integers::any(),
        );
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        // Insert at least one value to make sure the any() call will always
        // return true
        Insert::into(
            new Name('test'),
            Row::of([
                'id' => $this->uuid,
                'username' => $this->name,
                'registerNumber' => $this->number,
            ]),
        )->foreach($connection);
        $result1 = $connection(SQL::of('SELECT * FROM test'));
        $result2 = $connection(SQL::of('SELECT * FROM test'));

        // by using any() we only do a partial iteration over the results
        $assert->true($result1->any(static fn() => true));
        $assert->true($result2->any(static fn() => true));

        return $connection;
    }
}
