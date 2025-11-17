<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\Insert,
    Query\Select,
    Table\Name,
    Connection,
    Row,
};
use Innmind\Specification\{
    Comparator,
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
final class SelectWhereStartsWith implements Property
{
    private string $uuid;
    private string $prefix;
    private string $username;
    private int $number;

    public function __construct(
        string $uuid,
        string $prefix,
        string $username,
        int $number,
    ) {
        $this->uuid = $uuid;
        $this->prefix = $prefix;
        $this->username = $username;
        $this->number = $number;
    }

    public static function any(): Set
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Set::uuid(),
            Set::strings()
                ->madeOf(Set::strings()->chars()->ascii())
                ->between(10, 125), // 10 to avoid collisions with possible other values
            Set::strings()
                ->madeOf(Set::strings()->chars()->ascii())
                ->between(0, 125),
            Set::integers(),
        )->toSet();
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        $connection(Insert::into(
            Name::of('test'),
            Row::of([
                'id' => $this->uuid,
                'username' => $this->prefix.$this->username,
                'registerNumber' => $this->number,
            ]),
        ));

        $select = Select::from(Name::of('test'));
        $select = $select->where(Comparator\Property::of(
            'username',
            Sign::startsWith,
            $this->prefix,
        ));
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

        return $connection;
    }
}
