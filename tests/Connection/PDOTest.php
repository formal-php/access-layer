<?php
declare(strict_types = 1);

namespace Tests\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Connection\PDO,
    Connection,
    Query\SQL,
    Query\StartTransaction,
    Query\Commit,
    Query\Rollback,
    Query\CreateTable,
    Query\DropTable,
    Query\Parameter,
    Query\Parameter\Type,
    Table,
    Exception\QueryFailed,
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Formal\AccessLayer\Table\Name;
use Fixtures\Formal\AccessLayer\Table\Column;

class PDOTest extends TestCase
{
    use BlackBox;

    public function setUp(): void
    {
        $connection = $this->connection();
        $connection(DropTable::ifExists(new Table\Name('test')));
        $connection(new SQL('CREATE TABLE `test` (`id` varchar(36) NOT NULL,`username` varchar(255) NOT NULL, `registerNumber` bigint NOT NULL, PRIMARY KEY (id));'));
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Connection::class,
            $this->connection(),
        );
    }

    public function testAllowToStartTwoQueriesInParallel()
    {
        $connection = $this->connection();

        $result1 = $connection(new SQL('show tables'));
        $result2 = $connection(new SQL('show tables'));

        // by using any() we only do a partial iteration over the results
        $this->assertTrue($result1->any(static fn() => true));
        $this->assertTrue($result2->any(static fn() => true));
    }

    public function testThrowWhenInvalidQuery()
    {
        try {
            $query = new SQL('INSERT');
            $this->connection()($query);
            $this->fail('it should throw an exception');
        } catch (QueryFailed $e) {
            $this->assertSame($query, $e->query());
            $this->assertIsInt($e->code());
            $this->assertIsString($e->message());
        }
    }

    public function testThrowWhenNotEnoughParameters()
    {
        try {
            $query = new SQL('INSERT INTO `test` VALUES (:uuid, :username, :registerNumber);');
            $this->connection()($query);
            $this->fail('it should throw an exception');
        } catch (QueryFailed $e) {
            $this->assertSame($query, $e->query());
            $this->assertIsInt($e->code());
            $this->assertIsString($e->message());
        }
    }

    public function testThrowWhenValueDoesntFitTheSchema()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                $this->username(),
                Set\Integers::any(),
            )
            ->take(1)
            ->disableShrinking()
            ->then(function($uuid, $username, $number) {
                try {
                    $query = new SQL('INSERT INTO `test` VALUES (:uuid, :username, :registerNumber);');
                    $query = $query
                        ->with(Parameter::named('uuid', $uuid.$uuid)) // too long
                        ->with(Parameter::named('username', $username))
                        ->with(Parameter::named('registerNumber', $number));
                    $this->connection()($query);
                    $this->fail('it should throw an exception');
                } catch (QueryFailed $e) {
                    $this->assertSame($query, $e->query());
                }
            });
    }

    public function testInsert()
    {
        $this
            ->forAll(Set\Uuid::any())
            ->take(1)
            ->disableShrinking()
            ->then(function($uuid) {
                $connection = $this->connection();
                $select = new SQL('SELECT * FROM test');
                $rows = $connection($select);

                $this->assertCount(0, $rows);

                $sequence = $connection(new SQL("INSERT INTO `test` VALUES ('$uuid', 'foo', 42);"));

                $this->assertCount(0, $sequence);

                $rows = $connection($select);

                $this->assertCount(1, $rows);
                $this->assertSame($uuid, $rows->first()->column('id'));
                $this->assertSame('foo', $rows->first()->column('username'));
                $this->assertSame('42', $rows->first()->column('registerNumber'));
            });
    }

    public function testBindParameterByName()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                $this->username(),
                Set\Integers::any(),
            )
            ->take(1)
            ->disableShrinking()
            ->then(function($uuid, $username, $number) {
                $connection = $this->connection();
                $insert = new SQL('INSERT INTO `test` VALUES (:uuid, :username, :registerNumber);');
                $insert = $insert
                    ->with(Parameter::named('uuid', $uuid))
                    ->with(Parameter::named('username', $username))
                    ->with(Parameter::named('registerNumber', $number));
                $connection($insert);

                $rows = $connection(new SQL('SELECT * FROM `test`'));

                $this->assertCount(1, $rows);
                $this->assertSame($uuid, $rows->first()->column('id'));
                $this->assertSame($username, $rows->first()->column('username'));
                $this->assertSame((string) $number, $rows->first()->column('registerNumber'));
            });
    }

    public function testBindParameterByIndex()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                $this->username(),
                Set\Integers::any(),
            )
            ->take(1)
            ->disableShrinking()
            ->then(function($uuid, $username, $number) {
                $connection = $this->connection();
                $insert = new SQL('INSERT INTO `test` VALUES (?, ?, ?);');
                $insert = $insert
                    ->with(Parameter::of($uuid))
                    ->with(Parameter::of($username))
                    ->with(Parameter::of($number));
                $connection($insert);

                $rows = $connection(new SQL('SELECT * FROM `test`'));

                $this->assertCount(1, $rows);
                $this->assertSame($uuid, $rows->first()->column('id'));
                $this->assertSame($username, $rows->first()->column('username'));
                $this->assertSame((string) $number, $rows->first()->column('registerNumber'));
            });
    }

    public function testContentInsertedAfterStartOfTransactionIsAccessible()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                $this->username(),
                Set\Integers::any(),
            )
            ->take(1)
            ->disableShrinking()
            ->then(function($uuid, $username, $number) {
                $connection = $this->connection();

                $connection(new StartTransaction);

                $insert = new SQL('INSERT INTO `test` VALUES (?, ?, ?);');
                $insert = $insert
                    ->with(Parameter::of($uuid))
                    ->with(Parameter::of($username))
                    ->with(Parameter::of($number));
                $connection($insert);

                $rows = $connection(new SQL('SELECT * FROM `test`'));

                $this->assertCount(1, $rows);
                $this->assertSame($uuid, $rows->first()->column('id'));
                $this->assertSame($username, $rows->first()->column('username'));
                $this->assertSame((string) $number, $rows->first()->column('registerNumber'));
            });
    }

    public function testContentIsAccessibleAfterCommit()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                $this->username(),
                Set\Integers::any(),
            )
            ->take(1)
            ->disableShrinking()
            ->then(function($uuid, $username, $number) {
                $connection = $this->connection();

                $connection(new StartTransaction);

                $insert = new SQL('INSERT INTO `test` VALUES (?, ?, ?);');
                $insert = $insert
                    ->with(Parameter::of($uuid))
                    ->with(Parameter::of($username))
                    ->with(Parameter::of($number));
                $connection($insert);

                $connection(new Commit);

                $rows = $connection(new SQL('SELECT * FROM `test`'));

                $this->assertCount(1, $rows);
                $this->assertSame($uuid, $rows->first()->column('id'));
                $this->assertSame($username, $rows->first()->column('username'));
                $this->assertSame((string) $number, $rows->first()->column('registerNumber'));
            });
    }

    public function testContentIsNotAccessibleAfterRollback()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                $this->username(),
                Set\Integers::any(),
            )
            ->take(1)
            ->disableShrinking()
            ->then(function($uuid, $username, $number) {
                $connection = $this->connection();

                $connection(new StartTransaction);

                $insert = new SQL('INSERT INTO `test` VALUES (?, ?, ?);');
                $insert = $insert
                    ->with(Parameter::of($uuid))
                    ->with(Parameter::of($username))
                    ->with(Parameter::of($number));
                $connection($insert);

                $connection(new Rollback);

                $rows = $connection(new SQL('SELECT * FROM `test`'));

                $this->assertCount(0, $rows);
            });
    }

    public function testFailWhenCommittingUnstartedTransaction()
    {
        try {
            $query = new Commit;
            $this->connection()($query);
            $this->fail('it should throw an exception');
        } catch (QueryFailed $e) {
            $this->assertSame($query, $e->query());
        }
    }

    public function testFailWhenRollbackingUnstartedTransaction()
    {
        try {
            $query = new Rollback;
            $this->connection()($query);
            $this->fail('it should throw an exception');
        } catch (QueryFailed $e) {
            $this->assertSame($query, $e->query());
        }
    }

    public function testParameterTypesCanBeSpecified()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                $this->username(),
                Set\Integers::any(),
            )
            ->disableShrinking()
            ->then(function($uuid, $username, $number) {
                $connection = $this->connection();

                $insert = new SQL('INSERT INTO `test` VALUES (?, ?, ?);');
                $insert = $insert
                    ->with(Parameter::of($uuid, Type::string()))
                    ->with(Parameter::of($username, Type::string()))
                    ->with(Parameter::of($number, Type::int()));
                $connection($insert);

                $rows = $connection(new SQL("SELECT * FROM `test` WHERE id = '$uuid'"));

                $this->assertCount(1, $rows);
                $this->assertSame($username, $rows->first()->column('username'));
                $this->assertSame((string) $number, $rows->first()->column('registerNumber'));
            });
    }

    public function testCreateTable()
    {
        $this
            ->forAll(
                Name::any(),
                Column::list(),
            )
            ->then(function($name, $columns) {
                $connection = $this->connection();

                try {
                    $rows = $connection(new CreateTable($name, ...$columns));

                    $this->assertCount(0, $rows);
                } finally {
                    $connection(DropTable::ifExists($name));
                }
            });
    }

    public function testFailToCreateSameTableTwice()
    {
        $this
            ->forAll(
                Name::any(),
                Column::list(),
            )
            ->then(function($name, $columns) {
                $connection = $this->connection();

                try {
                    $connection(new CreateTable($name, ...$columns));
                    $connection($expected = new CreateTable($name, ...$columns));
                    $this->fail('it should throw');
                } catch (QueryFailed $e) {
                    $this->assertSame($expected, $e->query());
                } finally {
                    $connection(DropTable::ifExists($name));
                }
            });
    }

    public function testCreateTableIfNotExists()
    {
        $this
            ->forAll(
                Name::any(),
                Column::list(),
            )
            ->then(function($name, $columns) {
                $connection = $this->connection();

                try {
                    $connection(new CreateTable($name, ...$columns));
                    $rows = $connection(CreateTable::ifNotExists($name, ...$columns));

                    $this->assertCount(0, $rows);
                } finally {
                    $connection(DropTable::ifExists($name));
                }
            });
    }

    public function testCanDropUnknownDatabase()
    {
        $this
            ->forAll(Name::any())
            ->then(function($name) {
                $rows = $this->connection()(DropTable::ifExists($name));

                $this->assertCount(0, $rows);
            });
    }

    public function testThrowWhenDroppingUnknownDatabase()
    {
        $this
            ->forAll(Name::any())
            ->then(function($name) {
                try {
                    $query = new DropTable($name);
                    $this->connection()($query);
                    $this->fail('it should throw');
                } catch (QueryFailed $e) {
                    $this->assertSame($query, $e->query());
                }
            });
    }

    private function connection(): PDO
    {
        $port = \getenv('DB_PORT') ?: '3306';

        return new PDO(Url::of("mysql://root:root@127.0.0.1:$port/example"));
    }

    private function username(): Set
    {
        return Set\Strings::madeOf(Set\Chars::ascii())->between(0, 255);
    }
}
