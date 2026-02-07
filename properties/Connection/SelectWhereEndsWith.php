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
final class SelectWhereEndsWith implements Property
{
    private string $uuid;
    private string $suffix;
    private string $username;
    private int $number;

    public function __construct(
        string $uuid,
        string $suffix,
        string $username,
        int $number,
    ) {
        $this->uuid = $uuid;
        $this->suffix = $suffix;
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
                'username' => $this->username.$this->suffix,
                'registerNumber' => $this->number,
            ]),
        ));

        $select = Select::from(Name::of('test'));
        $select = $select->where(Comparator\Property::of(
            'username',
            Sign::endsWith,
            $this->suffix,
        ));
        $rows = $connection($select);

        $assert->same(1, $rows->size());
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
