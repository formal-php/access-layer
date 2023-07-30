<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Query,
    Table,
    Row,
    Connection,
};
use Innmind\Specification\{
    Comparator,
    Composable,
    Sign,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Connection>
 */
final class UpdateSpecificRow implements Property
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
        $connection(Query\Insert::into(
            new Table\Name('test'),
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
        ));

        $update = Query\Update::set(
            new Table\Name('test'),
            Row::of(['registerNumber' => 24]),
        );
        $update = $update->where(new class($this->uuid1) implements Comparator {
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
        });
        $sequence = $connection($update);

        $assert->count(0, $sequence);

        $rows = $connection(SQL::of("SELECT * FROM `test` WHERE `id` = '{$this->uuid1}'"));

        $assert->count(1, $rows);
        $assert->same(
            24,
            $rows
                ->first()
                ->flatMap(static fn($row) => $row->column('registerNumber'))
                ->match(
                    static fn($registerNumber) => $registerNumber,
                    static fn() => null,
                ),
        );

        $rows = $connection(SQL::of("SELECT * FROM `test` WHERE `id` = '{$this->uuid2}'"));

        $assert->count(1, $rows);
        $assert->same(
            42,
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
