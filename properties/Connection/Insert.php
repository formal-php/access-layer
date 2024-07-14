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
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Connection>
 */
final class Insert implements Property
{
    private string $uuid;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public static function any(): Set
    {
        return Set\Uuid::any()->map(static fn($uuid) => new self($uuid));
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        $select = SQL::of("SELECT * FROM test WHERE id = '{$this->uuid}'");
        $rows = $connection($select);

        $assert->count(0, $rows);

        $sequence = $connection(Query\Insert::into(
            Table\Name::of('test'),
            Row::of([
                'id' => $this->uuid,
                'username' => 'foo',
                'registerNumber' => 42,
            ]),
        ));

        $assert->count(0, $sequence);

        $rows = $connection($select);

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
            'foo',
            $rows
                ->first()
                ->flatMap(static fn($row) => $row->column('username'))
                ->match(
                    static fn($username) => $username,
                    static fn() => null,
                ),
        );
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
        $assert->same(
            [
                'id' => $this->uuid,
                'username' => 'foo',
                'registerNumber' => 42,
            ],
            $rows
                ->first()
                ->match(
                    static fn($row) => $row->toArray(),
                    static fn() => null,
                ),
        );

        return $connection;
    }
}
