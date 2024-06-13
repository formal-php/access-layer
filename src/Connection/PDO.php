<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Connection,
    Query,
    Query\Parameter,
    Query\Parameter\Type,
    Row,
    Exception\QueryFailed,
};
use Innmind\Url\{
    Url,
    Query as UrlQuery,
    Authority\UserInformation\User,
    Authority\UserInformation\Password,
};
use Innmind\Immutable\Sequence;

final class PDO implements Connection
{
    private \PDO $pdo;

    private function __construct(Url $dsn, array $options = [])
    {
        $dsnUser = $dsn->authority()->userInformation()->user();
        $dsnPassword = $dsn->authority()->userInformation()->password();
        $user = null;
        $password = null;

        if (!$dsnUser->equals(User::none())) {
            $user = $dsnUser->toString();
        }

        if (!$dsnPassword->equals(Password::none())) {
            $password = $dsnPassword->toString();
        }

        $this->pdo = new \PDO(
            self::parseDsn($dsn),
            $user,
            $password,
            $options,
        );
    }

    public function __invoke(Query $query): Sequence
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

    public static function of(Url $dsn): self
    {
        return new self($dsn);
    }

    public static function persistent(Url $dsn): self
    {
        return new self($dsn, [\PDO::ATTR_PERSISTENT => true]);
    }

    private function parseDsn(Url $dsn): string
    {
        $charset = '';

        if (!$dsn->query()->equals(UrlQuery::none())) {
            \parse_str($dsn->query()->toString(), $query);

            if (\array_key_exists('charset', $query)) {
                /** @psalm-suppress MixedOperand */
                $charset = ';charset='.$query['charset'];
            }
        }

        // si pas de port alors socket
        if (!$dsn->authority()->port()->value()) {
            $path = $dsn->path()->toString();
            $dbName = \basename($path);
            $socketPath = \dirname($path);

            return \sprintf(
                '%s:unix_socket=%s;dbname=%s%s',
                $dsn->scheme()->toString(),
                $socketPath,
                $dbName,
                $charset,
            );
        }

        return \sprintf(
            '%s:host=%s;port=%s;dbname=%s%s',
            $dsn->scheme()->toString(),
            $dsn->authority()->host()->toString(),
            $dsn->authority()->port()->toString(),
            \substr($dsn->path()->toString(), 1), // substring to remove leading '/'
            $charset,
        );
    }

    /**
     * @param callable(): bool $action
     *
     * @return Sequence<Row>
     */
    private function transaction(Query $query, callable $action): Sequence
    {
        $this->attempt($query, $action);

        /** @var Sequence<Row> */
        return Sequence::of();
    }

    /**
     * @return Sequence<Row>
     */
    private function execute(Query $query): Sequence
    {
        return match ($query->lazy()) {
            true => $this->lazy($query),
            false => $this->defer($query),
        };
    }

    /**
     * @return Sequence<Row>
     */
    private function lazy(Query $query): Sequence
    {
        /** @var Sequence<Row> */
        return Sequence::lazy(function() use ($query): \Generator {
            $statement = $this->prepare($query);

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
    private function defer(Query $query): Sequence
    {
        $statement = $this->prepare($query);

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
    private function prepare(Query $query): \PDOStatement
    {
        $statement = $this->pdo->prepare($query->sql());

        $_ = $query->parameters()->reduce(
            0,
            function(int $index, Parameter $parameter) use ($query, $statement): int {
                ++$index;
                $this->attempt(
                    $query,
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

        $this->attempt($query, static fn(): bool => $statement->execute());

        return $statement;
    }

    /**
     * @param callable(): bool $attempt
     *
     * @throws QueryFailed
     */
    private function attempt(Query $query, callable $attempt): void
    {
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
