<?php
declare(strict_types = 1);

namespace Tests\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Connection\PDO,
    Connection,
    Query\CreateTable,
    Query\DropTable,
    Query\Insert,
    Query\Select,
    Table\Name,
    Table\Column,
    Row,
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\PHPUnit\BlackBox;
use Properties\Formal\AccessLayer\Connection as PConnection;
use Fixtures\Formal\AccessLayer\Table\Name as FName;

class PDOTest extends TestCase
{
    use BlackBox;

    public function setUp(): void
    {
        PConnection::seed($this->connection());
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Connection::class,
            $this->connection(),
        );
    }

    /**
     * This test is not defined as a property because it takes about 2 minutes
     * and properties are run many times so it would take too much time to
     * execute all scenarii
     */
    public function testLazySelectDoesntLoadEverythingInMemory()
    {
        $this
            ->forAll(FName::any())
            ->take(1)
            ->disableShrinking()
            ->then(function($table) {
                $connection = $this->connection();

                $connection(CreateTable::named(
                    $table,
                    new Column(
                        new Column\Name('i'),
                        Column\Type::bigint(),
                    ),
                ));

                for ($i = 0; $i < 100_000; $i++) {
                    $insert = Insert::into(
                        $table,
                        Row::of([
                            'i' => $i,
                        ]),
                    );
                    $connection($insert);
                }

                $select = Select::onDemand($table);
                $rows = $connection($select);
                $memory = \memory_get_peak_usage();

                $count = $rows->reduce(
                    0,
                    static fn($count) => $count + 1,
                );

                $this->assertSame(100_000, $count);
                // when lazy this takes a little less than 3Mo of memory
                // when deferred this would take about 80Mo
                $this->assertLessThan(3_000_000, \memory_get_peak_usage() - $memory);

                $connection(DropTable::named($table));
            });
    }

    /**
     * @dataProvider properties
     */
    public function testHoldProperty($property)
    {
        $this
            ->forAll($property)
            ->then(function($property) {
                $connection = $this->connection();

                if (!$property->applicableTo($connection)) {
                    $this->markTestSkipped();
                }

                $property->ensureHeldBy($connection);
            });
    }

    public function testHoldProperties()
    {
        $this
            ->forAll(PConnection::properties())
            ->disableShrinking()
            ->then(function($properties) {
                $properties->ensureHeldBy($this->connection());
            });
    }

    /**
     * This test is not defined as a property because it takes about 2 minutes
     * and properties are run many times so it would take too much time to
     * execute all scenarii
     */
    public function testPersistentConnectionLazySelectDoesntLoadEverythingInMemory()
    {
        $this
            ->forAll(FName::any())
            ->take(1)
            ->disableShrinking()
            ->then(function($table) {
                $connection = $this->persistent();

                $connection(CreateTable::named(
                    $table,
                    new Column(
                        new Column\Name('i'),
                        Column\Type::bigint(),
                    ),
                ));

                for ($i = 0; $i < 100_000; $i++) {
                    $insert = Insert::into(
                        $table,
                        Row::of([
                            'i' => $i,
                        ]),
                    );
                    $connection($insert);
                }

                $select = Select::onDemand($table);
                $rows = $connection($select);
                $memory = \memory_get_peak_usage();

                $count = $rows->reduce(
                    0,
                    static fn($count) => $count + 1,
                );

                $this->assertSame(100_000, $count);
                // when lazy this takes a little less than 3Mo of memory
                // when deferred this would take about 80Mo
                $this->assertLessThan(3_000_000, \memory_get_peak_usage() - $memory);

                $connection(DropTable::named($table));
            });
    }

    /**
     * @dataProvider properties
     */
    public function testPersistentConnectionHoldProperty($property)
    {
        $this
            ->forAll($property)
            ->then(function($property) {
                $connection = $this->persistent();

                if (!$property->applicableTo($connection)) {
                    $this->markTestSkipped();
                }

                $property->ensureHeldBy($connection);
            });
    }

    public function testPersistentConnectionHoldProperties()
    {
        $this
            ->forAll(PConnection::properties())
            ->disableShrinking()
            ->then(function($properties) {
                $properties->ensureHeldBy($this->persistent());
            });
    }

    public function properties(): iterable
    {
        foreach (PConnection::list() as $property) {
            yield [$property];
        }
    }

    private function connection(): PDO
    {
        $port = \getenv('DB_PORT') ?: '3306';

        return PDO::of(Url::of("mysql://root:root@127.0.0.1:$port/example"));
    }

    private function persistent(): PDO
    {
        $port = \getenv('DB_PORT') ?: '3306';

        return PDO::persistent(Url::of("mysql://root:root@127.0.0.1:$port/example"));
    }
}
