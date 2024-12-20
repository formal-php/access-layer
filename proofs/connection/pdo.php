<?php
declare(strict_types = 1);
declare(ticks = 1);

use Formal\AccessLayer\{
    Connection,
    Connection\PDO,
    Query\CreateTable,
    Query\Constraint\ForeignKey,
    Query\Delete,
    Query\Insert,
    Query\MultipleInsert,
    Query\Select,
    Query\Select\Join,
    Query\DropTable,
    Row,
    Table,
    Table\Column,
    Driver,
};
use Properties\Formal\AccessLayer\Connection as Properties;
use Innmind\Url\{
    Url,
    Query,
};
use Innmind\Specification\{
    Comparator,
    Composable,
    Sign,
};
use Innmind\Immutable\Sequence;
use Innmind\BlackBox\Set;

$proofs = static function(Url $dsn, Driver $driver) {
    $connection = PDO::of($dsn);
    $persistent = PDO::persistent($dsn);
    Properties::seed($connection);
    $connections = Set\Either::any(
        Set\Call::of(static function() use ($connection) {
            Properties::seed($connection);

            return $connection;
        }),
        Set\Call::of(static function() use ($persistent) {
            Properties::seed($persistent);

            return $persistent;
        }),
    );

    yield test(
        "PDO interface({$driver->name})",
        static fn($assert) => $assert
            ->object($connection)
            ->instance(Connection::class),
    );

    $lazy = static fn($connection) => test(
        "PDO lazy select doesnt load everything in memory({$driver->name})",
        static function($assert) use ($connection) {
            $table = Table\Name::of('test_lazy_load');

            $connection(DropTable::ifExists($table));
            $connection(CreateTable::named(
                $table,
                Column::of(
                    Column\Name::of('i'),
                    Column\Type::bigint(),
                ),
            ));

            for ($i = 0; $i < 100_000; $i++) {
                $connection(Insert::into(
                    $table,
                    Row::of([
                        'i' => $i,
                    ]),
                ));
            }

            $select = Select::onDemand($table);
            $rows = $connection($select);

            // when lazy this takes a little less than 3Mo of memory
            // when deferred this would take about 80Mo
            $assert
                ->memory(static function() use ($assert, $rows) {
                    $count = $rows->reduce(
                        0,
                        static fn($count) => $count + 1,
                    );

                    $assert->same(100_000, $count);
                })
                ->inLessThan()
                ->megaBytes(3);

            $connection(DropTable::named($table));
        },
    );

    yield $lazy($connection);
    yield $lazy($persistent);

    if ($driver === Driver::mysql) {
        yield test(
            "PDO charset({$driver->name})",
            static function($assert) use ($connection, $dsn) {
                $table = Table\Name::of('test_charset');

                $connection(CreateTable::ifNotExists(
                    $table,
                    Column::of(
                        Column\Name::of('str'),
                        Column\Type::longtext(),
                    ),
                ));

                $connection(Insert::into(
                    $table,
                    Row::of([
                        'str' => 'gelé',
                    ]),
                ));

                $select = Select::from($table);

                $ascii = PDO::of($dsn->withQuery(Query::of('charset=ascii')));
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

                $utf8 = PDO::of($dsn->withQuery(Query::of('charset=utf8mb4')));
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
    }

    yield test(
        "Select join({$driver->name})",
        static function($assert) use ($connection) {
            $table = Table\Name::of('test_left_join');
            $connection(CreateTable::ifNotExists(
                $table,
                Column::of(
                    Column\Name::of('id'),
                    Column\Type::int(),
                ),
                Column::of(
                    Column\Name::of('name'),
                    Column\Type::varchar(),
                ),
                Column::of(
                    Column\Name::of('parent'),
                    Column\Type::int()->nullable(),
                ),
            ));
            $connection(Delete::from($table));
            $insert = MultipleInsert::into(
                $table,
                Column\Name::of('id'),
                Column\Name::of('name'),
                Column\Name::of('parent'),
            );
            $connection($insert(Sequence::of(
                Row::of([
                    'id' => 1,
                    'name' => 'value_1',
                    'parent' => null,
                ]),
                Row::of([
                    'id' => 2,
                    'name' => 'value_2',
                    'parent' => 1,
                ]),
                Row::of([
                    'id' => 3,
                    'name' => 'value_3',
                    'parent' => 1,
                ]),
                Row::of([
                    'id' => 4,
                    'name' => 'value_4',
                    'parent' => null,
                ]),
            )));

            $child = $table->as('child');
            $rows = $connection(
                Select::from($table->as('parent'))
                    ->columns(
                        Column\Name::of('name')->in($table->as('parent'))->as('parent'),
                        Column\Name::of('id')->in($child),
                        Column\Name::of('name')->in($child)->as('child'),
                    )
                    ->join(
                        Join::left($child)->on(
                            Column\Name::of('id')->in($table->as('parent')),
                            Column\Name::of('parent')->in($child),
                        ),
                    )
                    ->where(new class implements Comparator {
                        use Composable;

                        public function property(): string
                        {
                            return 'parent.id';
                        }

                        public function sign(): Sign
                        {
                            return Sign::equality;
                        }

                        public function value(): int
                        {
                            return 1;
                        }
                    }),
            );

            $assert
                ->expected([
                    [
                        'parent' => 'value_1',
                        'id' => 2,
                        'child' => 'value_2',
                    ],
                    [
                        'parent' => 'value_1',
                        'id' => 3,
                        'child' => 'value_3',
                    ],
                ])
                ->same(
                    $rows
                        ->map(static fn($row) => $row->toArray())
                        ->toList(),
                );

            $connection(DropTable::named($table));
        },
    );

    yield test(
        "Delete cascade({$driver->name})",
        static function($assert) use ($connection) {
            $parent = Table\Name::of('test_cascade_delete_parent');
            $child = Table\Name::of('test_cascade_delete_child');
            $connection(CreateTable::ifNotExists(
                $parent,
                Column::of(
                    Column\Name::of('id'),
                    Column\Type::int(),
                ),
            )->primaryKey(Column\Name::of('id')));
            $connection(CreateTable::ifNotExists(
                $child,
                Column::of(
                    Column\Name::of('id'),
                    Column\Type::int(),
                ),
                Column::of(
                    Column\Name::of('parent'),
                    Column\Type::int(),
                ),
            )->constraint(
                ForeignKey::of(Column\Name::of('parent'), $parent, Column\Name::of('id'))->onDeleteCascade(),
            ));
            $connection(Delete::from($child));
            $connection(Delete::from($parent));
            $connection(Insert::into(
                $parent,
                Row::of([
                    'id' => 1,
                ]),
            ));
            $insert = MultipleInsert::into(
                $child,
                Column\Name::of('id'),
                Column\Name::of('parent'),
            );
            $connection($insert(Sequence::of(
                Row::of([
                    'id' => 1,
                    'parent' => 1,
                ]),
                Row::of([
                    'id' => 2,
                    'parent' => 1,
                ]),
            )));

            $connection(Delete::from($parent));
            $rows = $connection(Select::from($child));

            $assert->count(0, $rows);

            $connection(DropTable::named($child));
            $connection(DropTable::named($parent));
        },
    );

    yield test(
        "Delete set null({$driver->name})",
        static function($assert) use ($connection) {
            $parent = Table\Name::of('test_set_null_delete_parent');
            $child = Table\Name::of('test_set_null_delete_child');
            $connection(CreateTable::ifNotExists(
                $parent,
                Column::of(
                    Column\Name::of('id'),
                    Column\Type::int(),
                ),
            )->primaryKey(Column\Name::of('id')));
            $connection(CreateTable::ifNotExists(
                $child,
                Column::of(
                    Column\Name::of('id'),
                    Column\Type::int(),
                ),
                Column::of(
                    Column\Name::of('parent'),
                    Column\Type::int()->nullable(),
                ),
            )->constraint(
                ForeignKey::of(Column\Name::of('parent'), $parent, Column\Name::of('id'))->onDeleteSetNull(),
            ));
            $connection(Delete::from($child));
            $connection(Delete::from($parent));
            $connection(Insert::into(
                $parent,
                Row::of([
                    'id' => 1,
                ]),
            ));
            $insert = MultipleInsert::into(
                $child,
                Column\Name::of('id'),
                Column\Name::of('parent'),
            );
            $connection($insert(Sequence::of(
                Row::of([
                    'id' => 1,
                    'parent' => 1,
                ]),
                Row::of([
                    'id' => 2,
                    'parent' => 1,
                ]),
            )));

            $connection(Delete::from($parent));
            $rows = $connection(Select::from($child))
                ->map(static fn($row) => $row->toArray())
                ->toList();

            $assert
                ->expected([
                    ['id' => 1, 'parent' => null],
                    ['id' => 2, 'parent' => null],
                ])
                ->same($rows);

            $connection(DropTable::named($child));
            $connection(DropTable::named($parent));
        },
    );

    yield test(
        "Foreign key name({$driver->name})",
        static function($assert) use ($driver) {
            $parent = Table\Name::of('parent_table');

            $assert->same(
                match ($driver) {
                    Driver::mysql => 'CONSTRAINT `FK_foo` FOREIGN KEY (`parent`) REFERENCES `parent_table`(`id`)',
                    Driver::postgres => 'CONSTRAINT "FK_foo" FOREIGN KEY ("parent") REFERENCES "parent_table"("id")',
                },
                ForeignKey::of(Column\Name::of('parent'), $parent, Column\Name::of('id'))
                    ->named('foo')
                    ->sql($driver),
            );
        },
    );

    yield test(
        "Delete join({$driver->name})",
        static function($assert) use ($connection) {
            $parent = Table\Name::of('test_join_delete_parent')->as('parent');
            $child = Table\Name::of('test_join_delete_child')->as('child');
            $connection(CreateTable::ifNotExists(
                $child->name(),
                Column::of(
                    Column\Name::of('id'),
                    Column\Type::int(),
                ),
            )->primaryKey(Column\Name::of('id')));
            $connection(
                CreateTable::ifNotExists(
                    $parent->name(),
                    Column::of(
                        Column\Name::of('id'),
                        Column\Type::int(),
                    ),
                    Column::of(
                        Column\Name::of('child'),
                        Column\Type::int(),
                    ),
                )
                    ->primaryKey(Column\Name::of('id'))
                    ->foreignKey(
                        Column\Name::of('child'),
                        $child->name(),
                        Column\Name::of('id'),
                    ),
            );
            $connection(Delete::from($parent->name()));
            $connection(Delete::from($child->name()));
            $connection(Insert::into(
                $child->name(),
                Row::of([
                    'id' => 2,
                ]),
            ));
            $connection(Insert::into(
                $parent->name(),
                Row::of([
                    'id' => 1,
                    'child' => 2,
                ]),
            ));

            $connection(
                Delete::from($parent)
                    ->join(
                        Join::left($child)->on(
                            Column\Name::of('child')->in($parent),
                            Column\Name::of('id')->in($child),
                        ),
                    )
                    ->where(Comparator\Property::of(
                        'child.id',
                        Sign::equality,
                        2,
                    )),
            );

            $rows = $connection(Select::from($child));
            $assert->count(1, $rows);

            $rows = $connection(Select::from($parent));
            $assert->count(0, $rows);

            $connection(DropTable::named($parent->name()));
            $connection(DropTable::named($child->name()));
        },
    );

    yield proof(
        "Unique constraint({$driver->name})",
        given(Set\Integers::between(0, 1_000_000)),
        static function($assert, $int) use ($connection) {
            $table = Table\Name::of('test_unique');
            $connection(CreateTable::ifNotExists(
                $table,
                Column::of(
                    Column\Name::of('id'),
                    Column\Type::int(),
                ),
                Column::of(
                    Column\Name::of('other'),
                    Column\Type::varchar(3),
                ),
            )->unique(Column\Name::of('id'), Column\Name::of('other')));

            $connection(Insert::into(
                $table,
                Row::of([
                    'id' => $int,
                    'other' => 'foo',
                ]),
            ));
            $connection(Insert::into(
                $table,
                Row::of([
                    'id' => $int,
                    'other' => 'bar',
                ]),
            ));

            $assert->throws(fn() => $connection(Insert::into(
                $table,
                Row::of([
                    'id' => $int,
                    'other' => 'foo',
                ]),
            )));

            $connection(DropTable::named($table));
        },
    );

    yield properties(
        "PDO properties({$driver->name})",
        Properties::any(),
        $connections,
    );

    foreach (Properties::list() as $property) {
        yield property(
            $property,
            $connections,
        )->named("PDO({$driver->name})");
    }
};

return static function() use ($proofs) {
    $port = \getenv('DB_PORT') ?: '3306';

    yield from $proofs(
        Url::of("mysql://root:root@127.0.0.1:$port/example"),
        Driver::mysql,
    );

    $port = \getenv('POSTGRES_DB_PORT') ?: '5432';

    yield from $proofs(
        Url::of("pgsql://root:root@127.0.0.1:$port/example"),
        Driver::postgres,
    );

    yield from $proofs(
        Url::of("sqlite://:memory:"),
        Driver::sqlite,
    );
};
