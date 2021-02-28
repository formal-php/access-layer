<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Connection,
    Query,
    Query\Parameter,
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
        if ($query instanceof Query\StartTransaction) {
            $this->attempt(
                $query,
                fn(): bool => $this->pdo->beginTransaction(),
            );

            return Sequence::of(Row::class);
        }

        if ($query instanceof Query\Commit) {
            $this->attempt(
                $query,
                fn(): bool => $this->pdo->commit(),
            );

            return Sequence::of(Row::class);
        }

        if ($query instanceof Query\Rollback) {
            $this->attempt(
                $query,
                fn(): bool => $this->pdo->rollBack(),
            );

            return Sequence::of(Row::class);
        }

        $statement = $this->pdo->prepare($query->toString());

        $query->parameters()->reduce(
            0,
            static function(int $index, Parameter $parameter) use ($statement): int {
                if ($parameter->boundByName()) {
                    $statement->bindValue($parameter->name(), $parameter->value());

                    return $index;
                }

                ++$index;
                $statement->bindValue($index, $parameter->value());

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
            $previous = null;
        } catch (\PDOException $e) {
            $previous = $e;
        }

        /** @var array{0: string, 1: ?string, 2: ?string} */
        $errorInfo = $this->pdo->errorInfo();

        throw new QueryFailed(
            $query,
            $errorInfo[0],
            $errorInfo[1],
            $errorInfo[2],
            $previous,
        );
    }
}
