<?php
declare(strict_types = 1);

namespace Tests\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Connection\PDO,
    Connection,
    Query\SQL,
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class PDOTest extends TestCase
{
    public function setUp(): void
    {
        $this->connection()(new SQL('CREATE TABLE IF NOT EXISTS `test` (`id` varchar(36) NOT NULL,`username` varchar(255) NOT NULL, PRIMARY KEY (id));'));
    }

    public function tearDown(): void
    {
        $this->connection()(new SQL('DROP TABLE `test`'));
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
        $connection = $this->connection();
        $select = new SQL('SELECT * FROM test');
        $rows = $connection($select);

        $this->assertCount(0, $rows);

        $sequence = $connection(new SQL("INSERT INTO `test` VALUES ('bb1ec590-2103-4220-9570-4e9eca632fc2', 'foo');"));

        $this->assertCount(0, $sequence);

        $rows = $connection($select);

        $this->assertCount(1, $rows);
        $this->assertSame('bb1ec590-2103-4220-9570-4e9eca632fc2', $rows->first()->column('id'));
        $this->assertSame('foo', $rows->first()->column('username'));
    }

    private function connection(): PDO
    {
        return new PDO(Url::of('mysql://root:root@127.0.0.1:3306/example'));
    }
}
