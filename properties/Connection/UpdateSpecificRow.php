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
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Set::uuid(),
            Set::uuid(),
        )->toSet();
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
        $_ = $connection($insert(Sequence::of(
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

        $update = Query\Update::set(
            Table\Name::of('test'),
            Row::of(['registerNumber' => 24]),
        );
        $update = $update->where(Comparator\Property::of(
            'id',
            Sign::equality,
            $this->uuid1,
        ));
        $sequence = $connection($update);

        $assert->same(0, $sequence->size());

        $rows = $connection(SQL::of("SELECT * FROM test WHERE id = '{$this->uuid1}'"));

        $assert->same(1, $rows->size());
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

        $rows = $connection(SQL::of("SELECT * FROM test WHERE id = '{$this->uuid2}'"));

        $assert->same(1, $rows->size());
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
