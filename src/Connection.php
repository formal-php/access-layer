<?php
declare(strict_types = 1);

namespace Formal\AccessLayer;

use Formal\AccessLayer\{
    Connection\Implementation,
    Connection\PDO,
    Connection\Lazy,
    Connection\Logger,
    Exception\QueryFailed,
};
use Innmind\Url\Url;
use Innmind\Immutable\Sequence;
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
    public function __invoke(Query $query): Sequence
    {
        return ($this->implementation)($query);
    }

    public static function new(Url $dsn): self
    {
        return new self(PDO::of($dsn));
    }

    /**
     * @param callable(): Connection $load
     */
    public static function lazy(callable $load): self
    {
        return new self(Lazy::of($load));
    }

    public static function logger(self $connection, LoggerInterface $logger): self
    {
        return new self(Logger::psr(
            $connection->implementation,
            $logger,
        ));
    }
}
