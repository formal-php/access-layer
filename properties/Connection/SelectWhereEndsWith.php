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
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            Set\Uuid::any(),
            Set\Strings::madeOf(Set\Chars::ascii())->between(10, 125), // 10 to avoid collisions with possible other values
            Set\Strings::madeOf(Set\Chars::ascii())->between(0, 125),
            Set\Integers::any(),
        );
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
        $select = $select->where(new class($this->suffix) implements Comparator {
            use Composable;

            public function __construct(private string $suffix)
            {
            }

            public function property(): string
            {
                return 'username';
            }

            public function sign(): Sign
            {
                return Sign::endsWith;
            }

            public function value(): string
            {
                return $this->suffix;
            }
        });
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
