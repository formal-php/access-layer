<?php
declare(strict_types = 1);

use Formal\AccessLayer\Connection;
use Properties\Formal\AccessLayer\Connection as Properties;
use Innmind\Url\Url;
use Innmind\BlackBox\Set;

return static function($prove) {
    $port = \getenv('DB_PORT') ?: '3306';
    $connection = Connection::new(Url::of("mysql://root:root@127.0.0.1:$port/example"))->unwrap();
    Properties::seed($connection);
    $connections = Set::of(static function() use ($connection) {
        Properties::seed($connection);

        return $connection;
    });

    yield $prove->test(
        'Lazy interface',
        static fn($assert) => $assert
            ->object($connection)
            ->instance(Connection::class),
    );

    yield $prove->test(
        'Lazy connection must not be established at instanciation',
        static fn($assert) => $assert
            ->object(Connection::new(Url::of('mysql://unknown:unknown@127.0.0.1:3306/unknown'))),
    );

    yield $prove->properties(
        'Lazy properties',
        Properties::any(),
        $connections,
    );

    foreach (Properties::list() as $property) {
        yield $prove
            ->property(
                $property,
                $connections,
            )
            ->named('Lazy');
    }
};
