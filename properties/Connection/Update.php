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
        return Set::uuid()->map(static fn($uuid) => new self($uuid));
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        $select = SQL::of("SELECT * FROM test WHERE id = '{$this->uuid}'");
        $connection(Query\Insert::into(
            Table\Name::of('test'),
            Row::of([
                'id' => $this->uuid,
                'username' => 'foo',
                'registerNumber' => 42,
            ]),
        ));

        $sequence = $connection(Query\Update::set(
            Table\Name::of('test'),
            Row::of(['registerNumber' => 24]),
        ));

        $assert->same(0, $sequence->size());

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
