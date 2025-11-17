<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Connection,
    Query,
    Driver,
};
use Innmind\Immutable\Sequence;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class Logger implements Implementation
{
    private Implementation $connection;
    private LoggerInterface $logger;

    private function __construct(Implementation $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    #[\Override]
    public function __invoke(Query|Query\Builder $query): Sequence
    {
        // For the sake of simplicity the queries SQL is logged with the MySQL
        // format. As otherwise it would require this decorator to retrieve the
        // driver from the underlying connection.

        if ($query instanceof Query\Builder) {
            $normalized = $query->normalize(Driver::mysql);
        } else {
            $normalized = $query;
        }

        try {
            $this->logger->debug(
                'Query {sql} is about to be executed',
                [
                    'sql' => $normalized->sql(),
                    'parameters' => $normalized->parameters()->reduce(
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
                    'sql' => $normalized->sql(),
                    'kind' => \get_class($e),
                    'message' => $e->getMessage(),
                ],
            );

            throw $e;
        }
    }

    public static function psr(Implementation $connection, LoggerInterface $logger): self
    {
        return new self($connection, $logger);
    }
}
