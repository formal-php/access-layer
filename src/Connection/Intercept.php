<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query,
    Driver,
    Row,
};
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class Intercept implements Implementation
{
    /**
     * @param \Closure(callable(Query|Query\Builder): Sequence<Row>, Driver, Query|Query\Builder): Sequence<Row> $intercept
     */
    private function __construct(
        private Implementation $connection,
        private \Closure $intercept,
    ) {
    }

    #[\Override]
    public function __invoke(Query|Query\Builder $query): Sequence
    {
        return ($this->intercept)(
            fn(Query|Query\Builder $query) => ($this->connection)($query),
            $this->connection->driver(),
            $query,
        );
    }

    /**
     * @internal
     *
     * @param \Closure(callable(Query|Query\Builder): Sequence<Row>, Driver, Query|Query\Builder): Sequence<Row> $intercept
     */
    public static function of(Implementation $connection, \Closure $intercept): self
    {
        return new self($connection, $intercept);
    }

    #[\Override]
    public function driver(): Driver
    {
        return $this->connection->driver();
    }
}
