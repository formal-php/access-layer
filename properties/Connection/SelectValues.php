<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\Insert,
    Query\Select,
    Table\Name,
    Table\Column,
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
use Fixtures\Formal\AccessLayer\Table\Column\Name as FName;

/**
 * @implements Property<Connection>
 */
final class SelectValues implements Property
{
    private function __construct(
        private string $uuid,
        private string $username,
        private int $number,
        private $valueName,
        private int|string|bool|null $value,
    ) {
    }

    public static function any(): Set
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Set::uuid(),
            Set::strings()
                ->madeOf(Set::strings()->chars()->ascii())
                ->between(0, 255),
            Set::integers(),
            FName::any(),
            Set::either(
                Set::integers(),
                Set::strings()->madeOf(
                    Set::strings()->chars()->alphanumerical(),
                ),
                Set::of(null, true, false),
            ),
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
                'username' => $this->username,
                'registerNumber' => $this->number,
            ]),
        ));

        $select = Select::from(Name::of('test'))
            ->columns(
                Column\Name::of('id'),
                Column\Name::of('username'),
                Column\Name::of('registerNumber'),
                Row\Value::of(
                    $this->valueName,
                    $this->value,
                ),
            )
            ->where(Comparator\Property::of(
                'id',
                Sign::equality,
                $this->uuid,
            ));
        $rows = $connection($select);

        $assert->same(1, $rows->size());
        $assert->same(
            $this->uuid,
            $rows
                ->first()
                ->flatMap(static fn($row) => $row->column('id'))
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
        );
        $assert->same(
            $this->username,
            $rows
                ->first()
                ->flatMap(static fn($row) => $row->column('username'))
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
        );
        $assert->same(
            $this->number,
            $rows
                ->first()
                ->flatMap(static fn($row) => $row->column('registerNumber'))
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
        );

        $value = $rows
            ->first()
            ->flatMap(fn($row) => $row->column($this->valueName->toString()))
            ->match(
                static fn($value) => $value,
                static fn() => null,
            );

        // Custom assertions here due to the way the different drivers interpret
        // them and return them.
        // Since selecting inline values should be used in an insert query the
        // drivers should handle casting values to the correct types internally.
        if ($this->value === true) {
            $assert
                ->array([1, 't'])
                ->contains($value);
        } else if ($this->value === false) {
            $assert
                ->array([0, 'f'])
                ->contains($value);
        } else if (\is_int($this->value)) {
            $assert
                ->array([$this->value, (string) $this->value])
                ->contains($value);
        } else {
            $assert->same($this->value, $value);
        }

        return $connection;
    }
}
