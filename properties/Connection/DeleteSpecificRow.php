<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Query,
    Table,
    Table\Column,
    Row,
    Connection,
};
use Innmind\Specification\{
    Comparator,
    Sign,
};
use Innmind\Immutable\Sequence;
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Connection>
 */
final class DeleteSpecificRow implements Property
{
    private string $uuid1;
    private string $uuid2;

    public function __construct(string $uuid1, string $uuid2)
    {
        $this->uuid1 = $uuid1;
        $this->uuid2 = $uuid2;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            Set\Uuid::any(),
            Set\Uuid::any(),
        );
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        $insert = Query\MultipleInsert::into(
            new Table\Name('test'),
            new Column\Name('id'),
            new Column\Name('username'),
            new Column\Name('registerNumber'),
        );
        $connection($insert(Sequence::of(
            Row::of([
                'id' => $this->uuid1,
                'username' => 'foo',
                'registerNumber' => 42,
            ]),
            Row::of([
                'id' => $this->uuid2,
                'username' => 'foo',
                'registerNumber' => 42,
            ]),
        )));

        $delete = Query\Delete::from(new Table\Name('test'))->where(
            Comparator\Property::of(
                'id',
                Sign::equality,
                $this->uuid1,
            ),
        );
        $sequence = $connection($delete);

        $assert->count(0, $sequence);

        $rows = $connection(SQL::of("SELECT * FROM test WHERE id = '{$this->uuid1}'"));

        $assert->count(0, $rows);

        $rows = $connection(SQL::of("SELECT * FROM test WHERE id = '{$this->uuid2}'"));

        $assert->count(1, $rows);

        return $connection;
    }
}
