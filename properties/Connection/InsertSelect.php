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
final class InsertSelect implements Property
{
    private function __construct(
        private string $uuid,
        private string $username,
        private int $number,
        private string $value,
    ) {
    }

    public static function any(): Set
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Set::uuid(),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->between(0, 100),
            Set::integers(),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->between(10, 100), // to avoid collisions
        )->toSet();
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        $select = SQL::of("SELECT * FROM test_values WHERE id = '{$this->uuid}'");
        $rows = $connection($select);

        $assert->same(0, $rows->size());

        $sequence = $connection(Query\Insert::into(
            Table\Name::of('test'),
            Row::of([
                'id' => $this->uuid,
                'username' => $this->username,
                'registerNumber' => $this->number,
            ]),
        ));

        $assert->same(0, $sequence->size());

        $sequence = $connection(Query\Insert::into(
            Table\Name::of('test_values'),
            Query\Select::from(Table\Name::of('test'))
                ->columns(
                    Row\Value::of(
                        Table\Column\Name::of('value'),
                        $this->value,
                    ),
                    Table\Column\Name::of('id'),
                )
                ->where(Comparator\Property::of(
                    'id',
                    Sign::equality,
                    $this->uuid,
                )),
        ));

        $assert->same(0, $sequence->size());

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
        $assert->same(
            $this->value,
            $rows
                ->first()
                ->flatMap(static fn($row) => $row->column('value'))
                ->match(
                    static fn($username) => $username,
                    static fn() => null,
                ),
        );

        $assert
            ->array(
                $connection(SQL::of("SELECT * FROM test_values WHERE id <> '{$this->uuid}'"))
                    ->flatMap(static fn($row) => $row->column('value')->toSequence())
                    ->toList(),
            )
            ->not()
            ->contains($this->value);

        return $connection;
    }
}
