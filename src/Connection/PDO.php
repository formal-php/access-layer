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
    Authority\UserInformation\User,
    Authority\UserInformation\Password,
};
use Innmind\Immutable\Sequence;

final class PDO implements Connection
{
    private \PDO $pdo;

    public function __construct(Url $dsn)
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

        $this->pdo = new \PDO(\sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            $dsn->scheme()->toString(),
            $dsn->authority()->host()->toString(),
            $dsn->authority()->port()->toString(),
            \substr($dsn->path()->toString(), 1), // substring to remove leading '/'
        ), $user, $password);
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

    /**
     * @param callable(): bool $action
     *
     * @return Sequence<Row>
     */
    private function transaction(Query $query, callable $action): Sequence
    {
        $this->attempt($query, $action);

        return Sequence::of(Row::class);
    }

    /**
     * @return Sequence<Row>
     */
    private function execute(Query $query): Sequence
    {
        $statement = $this->pdo->prepare($query->sql());

        $query->parameters()->reduce(
            0,
            function(int $index, Parameter $parameter) use ($query, $statement): int {
                if ($parameter->boundByName()) {
                    $this->attempt(
                        $query,
                        fn(): bool => $statement->bindValue(
                            $parameter->name(),
                            $parameter->value(),
                            $this->castType($parameter->type()),
                        ),
                    );

                    return $index;
                }

                ++$index;
                $this->attempt(
                    $query,
                    fn(): bool => $statement->bindValue(
                        $index,
                        $parameter->value(),
                        $this->castType($parameter->type()),
                    ),
                );

                return $index;
            },
        );

        $this->attempt($query, static fn(): bool => $statement->execute());

        /** @var Sequence<Row> */
        return Sequence::defer(
            Row::class,
            (static function(\PDOStatement $statement): \Generator {
                while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                    yield Row::of($row);
                }

                unset($statement);
            })($statement),
        );
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
            Type::bool() => \PDO::PARAM_BOOL,
            Type::null() => \PDO::PARAM_NULL,
            Type::int() => \PDO::PARAM_INT,
            Type::string() => \PDO::PARAM_STR,
            Type::unspecified() => \PDO::PARAM_STR, // this is the default of PDOStatement::bindValue()
        };
    }
}
