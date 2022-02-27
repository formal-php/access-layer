<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Connection,
    Query,
    Query\Parameter,
};
use Innmind\Immutable\Sequence;
use Psr\Log\LoggerInterface;

final class Logger implements Connection
{
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function __invoke(Query $query): Sequence
    {
        try {
            $this->logger->debug(
                'Query {sql} is about to be executed',
                [
                    'sql' => $query->sql(),
                    'parameters' => $query->parameters()->reduce(
                        [],
                        static fn(array $parameters, $parameter) => \array_merge(
                            $parameters,
                            $parameter->name()->match(
                                static fn($name) => [$name => $parameter->value()],
                                static fn() => [$parameter->value()],
                            ),
                        ),
                    ),
                ],
            );

            return ($this->connection)($query);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Query {sql} failed with {kind}({message})',
                [
                    'sql' => $query->sql(),
                    'kind' => \get_class($e),
                    'message' => $e->getMessage(),
                ],
            );

            throw $e;
        }
    }
}
