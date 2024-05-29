<?php
declare(strict_types = 1);

namespace Tests\Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Connection\Logger,
    Connection,
    Query\SQL,
    Query\Parameter,
    Row,
};
use Innmind\Immutable\Sequence;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class LoggerTest extends TestCase
{
    use BlackBox;

    public function testLogQuery()
    {
        $this
            ->forAll(
                Set\Strings::any(),
                Set\Integers::any(),
                Set\Integers::any(),
            )
            ->then(function($sql, $value1, $value2) {
                $query = SQL::of($sql);
                $query = $query
                    ->with(Parameter::of($value1))
                    ->with(Parameter::named('baz', $value2));
                $inner = $this->createMock(Connection::class);
                $inner
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($query)
                    ->willReturn($expected = Sequence::of(Row::class));
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects($this->once())
                    ->method('debug')
                    ->with(
                        'Query {sql} is about to be executed',
                        [
                            'sql' => $sql,
                            'parameters' => [0 => $value1, 'baz' => $value2],
                        ],
                    );

                $connection = Logger::psr($inner, $logger);

                $this->assertSame($expected, $connection($query));
            });
    }

    public function testLogFailedQuery()
    {
        $this
            ->forAll(
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($sql, $message) {
                $query = SQL::of($sql);
                $inner = $this->createMock(Connection::class);
                $inner
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($query)
                    ->will($this->throwException($expected = new \Exception($message)));
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects($this->once())
                    ->method('error')
                    ->with(
                        'Query {sql} failed with {kind}({message})',
                        [
                            'sql' => $sql,
                            'kind' => 'Exception',
                            'message' => $message,
                        ],
                    );

                $connection = Logger::psr($inner, $logger);

                try {
                    $connection($query);
                    $this->fail('it should throw');
                } catch (\Throwable $e) {
                    $this->assertSame($expected, $e);
                }
            });
    }
}
