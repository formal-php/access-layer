<?php
declare(strict_types = 1);

use Formal\AccessLayer\{
    Connection,
    Connection\PDO,
    Query\CreateTable,
    Query\Insert,
    Query\Select,
    Query\DropTable,
    Row,
    Table,
    Table\Column,
};
use Properties\Formal\AccessLayer\Connection as Properties;
use Innmind\Url\Url;
use Innmind\BlackBox\Set;

return static function() {
    $port = \getenv('DB_PORT') ?: '3306';
    $connection = PDO::of(Url::of("mysql://root:root@127.0.0.1:$port/example"));
    $persistent = PDO::persistent(Url::of("mysql://root:root@127.0.0.1:$port/example"));
    Properties::seed($connection);

    yield test(
        'PDO interface',
        static fn($assert) => $assert
            ->object($connection)
            ->instance(Connection::class),
    );

    $lazy = static fn($connection) => test(
        'PDO lazy select doesnt load everything in memory',
        static function($assert) use ($connection) {
            $table = Table\Name::of('test_lazy_load');

            $connection(CreateTable::named(
                $table,
                Column::of(
                    Column\Name::of('i'),
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

            $assert->same(100_000, $count);
            // when lazy this takes a little less than 3Mo of memory
            // when deferred this would take about 80Mo
            $assert
                ->number(\memory_get_peak_usage() - $memory)
                ->lessThan(3_000_000);

            $connection(DropTable::named($table));
        },
    );

    yield $lazy($connection);
    yield $lazy($persistent);

    yield test(
        'PDO charset',
        static function($assert) use ($connection, $port) {
            $table = Table\Name::of('test_charset');

            $connection(CreateTable::ifNotExists(
                $table,
                Column::of(
                    Column\Name::of('str'),
                    Column\Type::longtext(),
                ),
            ));

            $insert = Insert::into(
                $table,
                Row::of([
                    'str' => 'gelé',
                ]),
            );
            $connection($insert);

            $select = Select::from($table);

            $ascii = PDO::of(Url::of("mysql://root:root@127.0.0.1:$port/example?charset=ascii"));
            $assert
                ->expected('gelé')
                ->not()
                ->same(
                    $ascii($select)
                        ->first()
                        ->flatMap(static fn($row) => $row->column('str'))
                        ->match(
                            static fn($str) => $str,
                            static fn() => null,
                        ),
                );

            $utf8 = PDO::of(Url::of("mysql://root:root@127.0.0.1:$port/example?charset=utf8mb4"));
            $assert->same(
                'gelé',
                $utf8($select)
                    ->first()
                    ->flatMap(static fn($row) => $row->column('str'))
                    ->match(
                        static fn($str) => $str,
                        static fn() => null,
                    ),
            );

            $connection(DropTable::named($table));
        },
    );

    yield properties(
        'PDO properties',
        Properties::any(),
        Set\Elements::of($connection, $persistent),
    );

    foreach (Properties::list() as $property) {
        yield property(
            $property,
            Set\Elements::of($connection, $persistent),
        )->named('PDO');
    }
};
