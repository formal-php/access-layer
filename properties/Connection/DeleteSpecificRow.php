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
    Composable,
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
            Table\Name::of('test'),
            Column\Name::of('id'),
            Column\Name::of('username'),
            Column\Name::of('registerNumber'),
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

        $delete = Query\Delete::from(Table\Name::of('test'))->where(
            new class($this->uuid1) implements Comparator {
                use Composable;

                private string $uuid;

                public function __construct(string $uuid)
                {
                    $this->uuid = $uuid;
                }

                public function property(): string
                {
                    return 'id';
                }

                public function sign(): Sign
                {
                    return Sign::equality;
                }

                public function value(): string
                {
                    return $this->uuid;
                }
            },
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
