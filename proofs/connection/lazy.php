<?php
declare(strict_types = 1);

use Formal\AccessLayer\Connection;
use Properties\Formal\AccessLayer\Connection as Properties;
use Innmind\Url\Url;
use Innmind\BlackBox\Set;

return static function() {
    $port = \getenv('DB_PORT') ?: '3306';
    $connection = Connection::lazy(
        static fn() => Connection::new(Url::of("mysql://root:root@127.0.0.1:$port/example"))->unwrap(),
    );
    Properties::seed($connection);
    $connections = Set::call(static function() use ($connection) {
        Properties::seed($connection);

        return $connection;
    });

    yield test(
        'Lazy interface',
        static fn($assert) => $assert
            ->object($connection)
            ->instance(Connection::class),
    );

    yield test(
        'Lazy connection must not be established at instanciation',
        static fn($assert) => $assert
            ->object(Connection::lazy(static fn() => Connection::new(Url::of('mysql://unknown:unknown@127.0.0.1:3306/unknown'))->unwrap()))
            ->instance(Connection::class),
    );

    yield properties(
        'Lazy properties',
        Properties::any(),
        $connections,
    );

    foreach (Properties::list() as $property) {
        yield property(
            $property,
            $connections,
        )->named('Lazy');
    }
};
