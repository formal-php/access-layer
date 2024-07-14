<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Query\Parameter,
    Query\Select,
    Table\Name,
    Table\Column,
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
final class SelectWhereInQuery implements Property
{
    private string $uuid1;
    private string $uuid2;
    private string $username;
    private int $number;
    private string $value1;
    private string $value2;

    public function __construct(
        string $uuid1,
        string $uuid2,
        string $username,
        int $number,
        array $values,
    ) {
        $this->uuid1 = $uuid1;
        $this->uuid2 = $uuid2;
        $this->username = $username;
        $this->number = $number;
        [$this->value1, $this->value2] = $values;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            Set\Uuid::any(),
            Set\Uuid::any(),
            Set\Strings::madeOf(Set\Chars::ascii())->between(0, 255),
            Set\Integers::any(),
            Set\MutuallyExclusive::of(
                Set\Strings::madeOf(Set\Chars::ascii())->between(0, 255),
                Set\Strings::madeOf(Set\Chars::ascii())->between(0, 255),
            ),
        );
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        $insert = SQL::of('INSERT INTO test VALUES (?, ?, ?), (?, ?, ?);')
            ->with(Parameter::of($this->uuid1))
            ->with(Parameter::of($this->username))
            ->with(Parameter::of($this->number))
            ->with(Parameter::of($this->uuid2))
            ->with(Parameter::of($this->username))
            ->with(Parameter::of($this->number));
        $connection($insert);
        $insert = SQL::of('INSERT INTO test_values VALUES (?, ?), (?, ?);')
            ->with(Parameter::of($this->uuid1))
            ->with(Parameter::of($this->value1))
            ->with(Parameter::of($this->uuid1))
            ->with(Parameter::of($this->value2));
        $connection($insert);

        $select = Select::from(new Name('test'));
        $select = $select->where(Comparator\Property::of(
            'test.id',
            Sign::in,
            Select::from(new Name('test_values'))
                ->columns(new Column\Name('id'))
                ->where(
                    Comparator\Property::of(
                        'test_values.value',
                        Sign::equality,
                        $this->value1,
                    ),
                ),
        ));
        $rows = $connection($select);

        $assert->count(1, $rows);
        $assert->same(
            $this->uuid1,
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
