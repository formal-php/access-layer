<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
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
        if ($query instanceof Query\Builder) {
            $normalized = $query->normalize($this->driver());
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

    public function driver(): Driver
    {
        return $this->connection->driver();
    }
}
