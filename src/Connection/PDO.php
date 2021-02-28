<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Connection,
    Query,
    Query\Parameter,
    Row,
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
            $this->pdo->beginTransaction();

            return Sequence::of(Row::class);
        }

        if ($query instanceof Query\Commit) {
            $this->pdo->commit();

            return Sequence::of(Row::class);
        }

        if ($query instanceof Query\Rollback) {
            $this->pdo->rollBack();

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

        if (!$statement->execute()) {
            throw new \RuntimeException($query->toString());
        }

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
}
