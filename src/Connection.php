<?php
declare(strict_types = 1);

namespace Formal\AccessLayer;

use Formal\AccessLayer\{
    Connection\Implementation,
    Connection\PDO,
    Connection\Logger,
    Exception\QueryFailed,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Sequence,
    Attempt,
};
use Psr\Log\LoggerInterface;

final class Connection
{
    private function __construct(
        private Implementation $implementation,
    ) {
    }

    /**
     * @throws QueryFailed
     *
     * @return Sequence<Row>
     */
    public function __invoke(Query|Query\Builder $query): Sequence
    {
        return ($this->implementation)($query);
    }

    /**
     * @return Attempt<self>
     */
    public static function new(Url $dsn): Attempt
    {
        return PDO::of($dsn)->map(
            static fn($implementation) => new self($implementation),
        );
    }

    public static function logger(self $connection, LoggerInterface $logger): self
    {
        return new self(Logger::psr(
            $connection->implementation,
            $logger,
        ));
    }
}
