<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Exception;

use Formal\AccessLayer\Query;

final class QueryFailed extends RuntimeException
{
    private Query|Query\Builder $query;
    private string $sqlstate;
    private ?int $driverSpecificCode;
    private ?string $driverSpecificMessage;

    public function __construct(
        Query|Query\Builder $query,
        Query $normalized,
        string $sqlstate,
        ?int $code,
        ?string $message,
        ?\Throwable $previous,
    ) {
        $this->query = $query;
        $this->sqlstate = $sqlstate;
        $this->driverSpecificCode = $code;
        $this->driverSpecificMessage = $message;
        parent::__construct(\sprintf(
            "Query '%s' failed with: [%s] [%s] %s",
            $normalized->sql(),
            $sqlstate,
            (string) $code,
            (string) $message,
        ), 0, $previous);
    }

    public function query(): Query|Query\Builder
    {
        return $this->query;
    }

    public function sqlstate(): string
    {
        return $this->sqlstate;
    }

    public function code(): ?int
    {
        return $this->driverSpecificCode;
    }

    public function message(): ?string
    {
        return $this->driverSpecificMessage;
    }
}
