<?php
declare(strict_types = 1);

namespace Tests\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Connection\Lazy,
    Connection,
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\PHPUnit\BlackBox;
use Properties\Formal\AccessLayer\Connection as PConnection;

class LazyTest extends TestCase
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

    public function testConnectionNotEstablishedAtInstanciation()
    {
        $this->assertInstanceOf(
            Connection::class,
            new Lazy(Url::of('mysql://unknown:unknown@127.0.0.1:3306/unknown')),
        );
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

    public function properties(): iterable
    {
        foreach (PConnection::list() as $property) {
            yield [$property];
        }
    }

    private function connection(): Connection
    {
        $port = \getenv('DB_PORT') ?: '3306';

        return new Lazy(Url::of("mysql://root:root@127.0.0.1:$port/example"));
    }
}
