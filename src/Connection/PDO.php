<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query,
    Query\Parameter,
    Query\Parameter\Type,
    Row,
    Driver,
    Exception\QueryFailed,
};
use Innmind\Url\{
    Url,
    Query as UrlQuery,
    Authority\UserInformation\User,
    Authority\UserInformation\Password,
};
use Innmind\Immutable\{
    Sequence,
    Attempt,
};

/**
 * @internal
 */
final class PDO implements Implementation
{
    private \PDO $pdo;
    private Driver $driver;

    private function __construct(Url $dsn)
    {
        $dsnUser = $dsn->authority()->userInformation()->user();
        $dsnPassword = $dsn->authority()->userInformation()->password();
        $user = null;
        $password = null;
        $charset = '';

        if (!$dsnUser->equals(User::none())) {
            $user = $dsnUser->toString();
        }

        if (!$dsnPassword->equals(Password::none())) {
            $password = $dsnPassword->toString();
        }

        $this->driver = match ($dsn->scheme()->toString()) {
            'mysql' => Driver::mysql,
            'pgsql' => Driver::postgres,
        };

        if (!$dsn->query()->equals(UrlQuery::none())) {
            \parse_str($dsn->query()->toString(), $query);

            if (\array_key_exists('charset', $query)) {
                /** @psalm-suppress MixedOperand */
                $charset = match ($this->driver) {
                    Driver::postgres => ";options='--client_encoding={$query['charset']}'",
                    default => ';charset='.$query['charset'],
                };
            }
        }

        $pdoDsn = \sprintf(
            '%s:host=%s;port=%s;dbname=%s%s',
            $dsn->scheme()->toString(),
            $dsn->authority()->host()->toString(),
            $dsn->authority()->port()->toString(),
            \substr($dsn->path()->toString(), 1), // substring to remove leading '/'
            $charset,
        );

        $this->pdo = new \PDO($pdoDsn, $user, $password);
    }

    #[\Override]
    public function __invoke(Query|Query\Builder $query): Sequence
    {
        return match (\get_class($query)) {
            Query\StartTransaction::class => $this->transaction(
                $query,
                fn(): bool => $this->pdo->beginTransaction(),
            ),
            Query\Commit::class => $this->transaction(
                $query,
                fn(): bool => $this->pdo->commit(),
            ),
            Query\Rollback::class => $this->transaction(
                $query,
                fn(): bool => $this->pdo->rollBack(),
            ),
            default => $this->execute($query),
        };
    }

    /**
     * @return Attempt<self>
     */
    public static function of(Url $dsn): Attempt
    {
        return Attempt::defer(
            static fn() => Attempt::of(static fn() => new self($dsn)),
        );
    }

    public function driver(): Driver
    {
        return $this->driver;
    }

    /**
     * @param callable(): bool $action
     *
     * @return Sequence<Row>
     */
    private function transaction(Query\Builder $query, callable $action): Sequence
    {
        $this->attempt(
            $query,
            $query->normalize($this->driver),
            $action,
        );

        /** @var Sequence<Row> */
        return Sequence::of();
    }

    /**
     * @return Sequence<Row>
     */
    private function execute(Query|Query\Builder $query): Sequence
    {
        if ($query instanceof Query\Builder) {
            $normalized = $query->normalize($this->driver);
        } else {
            $normalized = $query;
        }

        return match ($normalized->lazy()) {
            true => $this->lazy($query, $normalized),
            false => $this->defer($query, $normalized),
        };
    }

    /**
     * @return Sequence<Row>
     */
    private function lazy(
        Query|Query\Builder $query,
        Query $normalized,
    ): Sequence {
        /** @var Sequence<Row> */
        return Sequence::lazy(function() use ($query, $normalized): \Generator {
            $statement = $this->prepare($query, $normalized);

            /** @psalm-suppress MixedAssignment */
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                /** @psalm-suppress MixedArgument */
                yield Row::of($row);
            }

            unset($statement);
        });
    }

    /**
     * @return Sequence<Row>
     */
    private function defer(
        Query|Query\Builder $query,
        Query $normalized,
    ): Sequence {
        $statement = $this->prepare($query, $normalized);

        /** @var Sequence<Row> */
        return Sequence::defer(
            (static function(\PDOStatement $statement): \Generator {
                /** @psalm-suppress MixedAssignment */
                while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                    /** @psalm-suppress MixedArgument */
                    yield Row::of($row);
                }

                unset($statement);
            })($statement),
        );
    }

    /**
     * @throws QueryFailed
     */
    private function prepare(
        Query|Query\Builder $query,
        Query $normalized,
    ): \PDOStatement {
        $statement = $this->guard(
            $query,
            $normalized,
            fn() => $this->pdo->prepare($normalized->sql()),
        );

        $_ = $normalized->parameters()->reduce(
            0,
            function(int $index, Parameter $parameter) use ($query, $normalized, $statement): int {
                ++$index;
                $this->attempt(
                    $query,
                    $normalized,
                    fn(): bool => $statement->bindValue(
                        $parameter->name()->match(
                            static fn($name) => $name,
                            static fn() => $index,
                        ),
                        $parameter->value(),
                        $this->castType($parameter->type()),
                    ),
                );

                return $index;
            },
        );

        $this->attempt(
            $query,
            $normalized,
            static fn(): bool => $statement->execute(),
        );

        return $statement;
    }

    /**
     * @param callable(): (\PDOStatement|false) $try
     *
     * @throws QueryFailed
     */
    private function guard(
        Query|Query\Builder $query,
        Query $normalized,
        callable $try,
    ): \PDOStatement {
        try {
            $statement = $try();

            if ($statement === false) {
                throw new \Exception;
            }

            return $statement;
        } catch (\PDOException $e) {
            /** @var array{0: string, 1: ?int, 2: ?string} */
            $errorInfo = $e->errorInfo ?? $this->pdo->errorInfo();
            $previous = $e;
        } catch (\Throwable $e) {
            /** @var array{0: string, 1: ?int, 2: ?string} */
            $errorInfo = $this->pdo->errorInfo();
            $previous = null;
        }

        throw new QueryFailed(
            $query,
            $normalized,
            $errorInfo[0],
            $errorInfo[1],
            $errorInfo[2],
            $previous,
        );
    }

    /**
     * @param callable(): bool $attempt
     *
     * @throws QueryFailed
     */
    private function attempt(
        Query|Query\Builder $query,
        Query $normalized,
        callable $attempt,
    ): void {
        try {
            if ($attempt()) {
                return;
            }
            /** @var array{0: string, 1: ?int, 2: ?string} */
            $errorInfo = $this->pdo->errorInfo();
            $previous = null;
        } catch (\PDOException $e) {
            /** @var array{0: string, 1: ?int, 2: ?string} */
            $errorInfo = $e->errorInfo ?? $this->pdo->errorInfo();
            $previous = $e;
        }

        throw new QueryFailed(
            $query,
            $normalized,
            $errorInfo[0],
            $errorInfo[1],
            $errorInfo[2],
            $previous,
        );
    }

    private function castType(Type $type): int
    {
        return match ($type) {
            Type::bool => \PDO::PARAM_BOOL,
            Type::null => \PDO::PARAM_NULL,
            Type::int => \PDO::PARAM_INT,
            Type::string => \PDO::PARAM_STR,
            Type::unspecified => \PDO::PARAM_STR, // this is the default of PDOStatement::bindValue()
        };
    }
}
