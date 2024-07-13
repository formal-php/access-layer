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
final class SelectWhereContains implements Property
{
    private string $uuid;
    private string $prefix;
    private string $suffix;
    private string $username;
    private int $number;

    public function __construct(
        string $uuid,
        string $prefix,
        string $suffix,
        string $username,
        int $number,
    ) {
        $this->uuid = $uuid;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        $this->username = $username;
        $this->number = $number;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            Set\Uuid::any(),
            Set\Strings::madeOf(Set\Chars::ascii())->between(0, 100),
            Set\Strings::madeOf(Set\Chars::ascii())->between(0, 100),
            Set\Strings::madeOf(Set\Chars::ascii())->between(10, 55), // 10 to avoid collisions with possible other values
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
            new Name('test'),
            Row::of([
                'id' => $this->uuid,
                'username' => $this->prefix.$this->username.$this->suffix,
                'registerNumber' => $this->number,
            ]),
        ));

        $select = Select::from(new Name('test'));
        $select = $select->where(new class($this->username) implements Comparator {
            use Composable;

            public function __construct(private string $username)
            {
            }

            public function property(): string
            {
                return 'username';
            }

            public function sign(): Sign
            {
                return Sign::contains;
            }

            public function value(): string
            {
                return $this->username;
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
