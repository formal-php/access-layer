<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query\SQL,
    Query\Parameter,
    Exception\QueryFailed,
    Connection,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Connection>
 */
final class MustThrowWhenValueDoesntFitTheSchema implements Property
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
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Set::uuid(),
            Set::strings()
                ->madeOf(Set::strings()->chars()->ascii())
                ->between(0, 255),
            Set::integers(),
        )->toSet();
    }

    public function applicableTo(object $connection): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $connection): object
    {
        try {
            $query = SQL::of('INSERT INTO test VALUES (:uuid, :username, :registerNumber);');
            $query = $query
                ->with(Parameter::named('uuid', $this->uuid.$this->uuid)) // too long
                ->with(Parameter::named('username', $this->username))
                ->with(Parameter::named('registerNumber', $this->number));
            $connection($query);
            $assert->fail('it should throw an exception');
        } catch (QueryFailed $e) {
            $assert->same($query, $e->query());
        }

        return $connection;
    }
}
