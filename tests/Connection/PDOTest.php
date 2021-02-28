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
    Query\Parameter,
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class PDOTest extends TestCase
{
    use BlackBox;

    public function setUp(): void
    {
        $connection = $this->connection();
        $connection(new SQL('DROP TABLE IF EXISTS `test`'));
        $connection(new SQL('CREATE TABLE `test` (`id` varchar(36) NOT NULL,`username` varchar(255) NOT NULL, PRIMARY KEY (id));'));
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

                $sequence = $connection(new SQL("INSERT INTO `test` VALUES ('$uuid', 'foo');"));

                $this->assertCount(0, $sequence);

                $rows = $connection($select);

                $this->assertCount(1, $rows);
                $this->assertSame($uuid, $rows->first()->column('id'));
                $this->assertSame('foo', $rows->first()->column('username'));
            });
    }

    public function testBindParameterByName()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                $this->username(),
            )
            ->take(1)
            ->disableShrinking()
            ->then(function($uuid, $username) {
                $connection = $this->connection();
                $insert = new SQL('INSERT INTO `test` VALUES (:uuid, :username);');
                $insert = $insert
                    ->with(Parameter::named('uuid', $uuid))
                    ->with(Parameter::named('username', $username));
                $connection($insert);

                $rows = $connection(new SQL('SELECT * FROM `test`'));

                $this->assertCount(1, $rows);
                $this->assertSame($uuid, $rows->first()->column('id'));
                $this->assertSame($username, $rows->first()->column('username'));
            });
    }

    public function testBindParameterByIndex()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                $this->username(),
            )
            ->take(1)
            ->disableShrinking()
            ->then(function($uuid, $username) {
                $connection = $this->connection();
                $insert = new SQL('INSERT INTO `test` VALUES (?, ?);');
                $insert = $insert
                    ->with(Parameter::of($uuid))
                    ->with(Parameter::of($username));
                $connection($insert);

                $rows = $connection(new SQL('SELECT * FROM `test`'));

                $this->assertCount(1, $rows);
                $this->assertSame($uuid, $rows->first()->column('id'));
                $this->assertSame($username, $rows->first()->column('username'));
            });
    }

    public function testContentInsertedAfterStartOfTransactionIsAccessible()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                $this->username(),
            )
            ->take(1)
            ->disableShrinking()
            ->then(function($uuid, $username) {
                $connection = $this->connection();

                $connection(new StartTransaction);

                $insert = new SQL('INSERT INTO `test` VALUES (?, ?);');
                $insert = $insert
                    ->with(Parameter::of($uuid))
                    ->with(Parameter::of($username));
                $connection($insert);

                $rows = $connection(new SQL('SELECT * FROM `test`'));

                $this->assertCount(1, $rows);
                $this->assertSame($uuid, $rows->first()->column('id'));
                $this->assertSame($username, $rows->first()->column('username'));
            });
    }

    public function testContentIsAccessibleAfterCommit()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                $this->username(),
            )
            ->take(1)
            ->disableShrinking()
            ->then(function($uuid, $username) {
                $connection = $this->connection();

                $connection(new StartTransaction);

                $insert = new SQL('INSERT INTO `test` VALUES (?, ?);');
                $insert = $insert
                    ->with(Parameter::of($uuid))
                    ->with(Parameter::of($username));
                $connection($insert);

                $connection(new Commit);

                $rows = $connection(new SQL('SELECT * FROM `test`'));

                $this->assertCount(1, $rows);
                $this->assertSame($uuid, $rows->first()->column('id'));
                $this->assertSame($username, $rows->first()->column('username'));
            });
    }

    public function testContentIsNotAccessibleAfterRollback()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                $this->username(),
            )
            ->take(1)
            ->disableShrinking()
            ->then(function($uuid, $username) {
                $connection = $this->connection();

                $connection(new StartTransaction);

                $insert = new SQL('INSERT INTO `test` VALUES (?, ?);');
                $insert = $insert
                    ->with(Parameter::of($uuid))
                    ->with(Parameter::of($username));
                $connection($insert);

                $connection(new Rollback);

                $rows = $connection(new SQL('SELECT * FROM `test`'));

                $this->assertCount(0, $rows);
            });
    }

    public function testFailWhenCommittingUnstartedTransaction()
    {
        $this->expectException(\Exception::class);

        $this->connection()(new Commit);
    }

    public function testFailWhenRollbackingUnstartedTransaction()
    {
        $this->expectException(\Exception::class);

        $this->connection()(new Rollback);
    }

    private function connection(): PDO
    {
        return new PDO(Url::of('mysql://root:root@127.0.0.1:3306/example'));
    }

    private function username(): Set
    {
        return Set\Decorate::immutable(
            static fn(array $chars): string => \implode('', $chars),
            Set\Sequence::of(
                Set\Decorate::immutable(
                    static fn(int $ord): string => \chr($ord),
                    Set\Integers::between(32, 126),
                ),
                Set\Integers::between(0, 255),
            ),
        );
    }
}
