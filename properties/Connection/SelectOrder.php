<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\Insert,
    Query\Select,
    Query\Select\Direction,
    Table\Name,
    Table\Column,
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
final class SelectOrder implements Property
{
    private string $uuid1;
    private string $uuid2;
    private string $username;
    private int $number;

    public function __construct(
        string $uuid1,
        string $uuid2,
        string $username,
        int $number,
    ) {
        $this->uuid1 = $uuid1;
        $this->uuid2 = $uuid2;
        $this->username = $username;
        $this->number = $number;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            Set\Uuid::any(),
            Set\Uuid::any(),
            Set\Strings::madeOf(Set\Chars::ascii())->between(0, 254),
            Set\Integers::any(),
        );
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        Insert::into(
            new Name('test'),
            Row::of([
                'id' => $this->uuid1,
                'username' => 'a'.$this->username,
                'registerNumber' => $this->number,
            ]),
            Row::of([
                'id' => $this->uuid2,
                'username' => 'b'.$this->username,
                'registerNumber' => $this->number,
            ]),
        )->foreach($connection);

        $table = Name::of('test');
        $select = Select::from($table)
            ->orderBy(
                Column\Name::of('username')->in($table),
                Direction::asc,
            )
            ->where(new class([$this->uuid1, $this->uuid2]) implements Comparator {
                use Composable;

                public function __construct(
                    private array $values,
                ) {
                }

                public function property(): string
                {
                    return 'id';
                }

                public function sign(): Sign
                {
                    return Sign::in;
                }

                public function value(): array
                {
                    return $this->values;
                }
            });
        $rows = $connection($select);

        $assert
            ->expected([$this->uuid1, $this->uuid2])
            ->same(
                $rows
                    ->flatMap(static fn($row) => $row->column('id')->toSequence())
                    ->toList(),
            );

        $rows = $connection($select->orderBy(
            Column\Name::of('username')->in($table),
            Direction::desc,
        ));

        $assert
            ->expected([$this->uuid2, $this->uuid1])
            ->same(
                $rows
                    ->flatMap(static fn($row) => $row->column('id')->toSequence())
                    ->toList(),
            );

        return $connection;
    }
}
