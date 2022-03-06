<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Query\Parameter,
    Query\Select,
    Table\Name,
};
use Innmind\Specification\{
    Comparator,
    Composable,
    Sign,
};
use Innmind\BlackBox\{
    Property,
    Set,
};
use PHPUnit\Framework\Assert;

final class SelectWhere implements Property
{
    private string $uuid;
    private string $username;
    private int $number;

    public function __construct(string $uuid, string $username, int $number)
    {
        $this->uuid = $uuid;
        $this->username = $username;
        $this->number = $number;
    }

    public static function any(): Set
    {
        return Set\Property::of(
            self::class,
            Set\Uuid::any(),
            Set\Strings::madeOf(Set\Chars::ascii())->between(0, 255),
            Set\Integers::any(),
        );
    }

    public function name(): string
    {
        return 'Select where';
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(object $connection): object
    {
        $insert = SQL::of('INSERT INTO `test` VALUES (?, ?, ?);')
            ->with(Parameter::of($this->uuid))
            ->with(Parameter::of($this->username))
            ->with(Parameter::of($this->number));
        $connection($insert);

        $select = Select::from(new Name('test'));
        $select = $select->where(new class($this->uuid) implements Comparator {
            use Composable;

            private string $uuid;

            public function __construct(string $uuid)
            {
                $this->uuid = $uuid;
            }

            public function property(): string
            {
                return 'id';
            }

            public function sign(): Sign
            {
                return Sign::equality;
            }

            public function value(): string
            {
                return $this->uuid;
            }
        });
        $rows = $connection($select);

        Assert::assertCount(1, $rows);
        Assert::assertSame($this->uuid, $rows->first()->match(
            static fn($row) => $row->column('id'),
            static fn() => null,
        ));

        return $connection;
    }
}
