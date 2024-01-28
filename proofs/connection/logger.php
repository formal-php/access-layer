<?php
declare(strict_types = 1);

use Formal\AccessLayer\{
    Connection,
    Connection\PDO,
    Connection\Logger,
};
use Properties\Formal\AccessLayer\Connection as Properties;
use Innmind\Url\Url;
use Innmind\BlackBox\Set;
use Psr\Log\NullLogger;

return static function() {
    $port = \getenv('DB_PORT') ?: '3306';
    $connection = Logger::psr(
        PDO::of(Url::of("mysql://root:root@127.0.0.1:$port/example")),
        new NullLogger,
    );
    Properties::seed($connection);
    $connections = Set\Call::of(static function() use ($connection) {
        Properties::seed($connection);

        return $connection;
    });

    yield test(
        'Logger interface',
        static fn($assert) => $assert
            ->object($connection)
            ->instance(Connection::class),
    );

    yield properties(
        'Logger properties',
        Properties::any(),
        $connections,
    );

    foreach (Properties::list() as $property) {
        yield property(
            $property,
            $connections,
        )->named('Logger');
    }
};
