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
final class Update implements Property
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
        $connection(Query\Insert::into(
            new Table\Name('test'),
            Row::of([
                'id' => $this->uuid,
                'username' => 'foo',
                'registerNumber' => 42,
            ]),
        ));

        $sequence = $connection(Query\Update::set(
            new Table\Name('test'),
            Row::of(['registerNumber' => 24]),
        ));

        $assert->count(0, $sequence);

        $rows = $connection($select);

        $assert
            ->number($rows->size())
            ->greaterThanOrEqual(1);
        $rows->foreach(static fn($row) => $assert->same(24, $row->column('registerNumber')->match(
            static fn($registerNumber) => $registerNumber,
            static fn() => null,
        )));

        return $connection;
    }
}
