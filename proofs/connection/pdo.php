<?php
declare(strict_types = 1);

use Formal\AccessLayer\{
    Connection,
    Connection\PDO,
    Query\CreateTable,
    Query\Constraint\ForeignKey,
    Query\Delete,
    Query\Insert,
    Query\Update,
    Query\Select,
    Query\Select\Join,
    Query\DropTable,
    Row,
    Table,
    Table\Column,
};
use Properties\Formal\AccessLayer\Connection as Properties;
use Innmind\Url\Url;
use Innmind\Specification\{
    Comparator,
    Composable,
    Sign,
};
use Innmind\BlackBox\Set;

return static function() {
    $port = \getenv('DB_PORT') ?: '3306';
    $connection = PDO::of(Url::of("mysql://root:root@127.0.0.1:$port/example"));
    $persistent = PDO::persistent(Url::of("mysql://root:root@127.0.0.1:$port/example"));
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

    yield test(
        'Select join',
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
            $connection(Insert::into(
                $table,
                Row::of([
                    'id' => 1,
                    'name' => 'value_1',
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
                ]),
            ));

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
        'Delete cascade',
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
            $connection(Insert::into(
                $parent,
                Row::of([
                    'id' => 1,
                ]),
            ));
            $connection(Insert::into(
                $child,
                Row::of([
                    'id' => 1,
                    'parent' => 1,
                ]),
                Row::of([
                    'id' => 2,
                    'parent' => 1,
                ]),
            ));

            $connection(Delete::from($parent));
            $rows = $connection(Select::from($child));

            $assert->count(0, $rows);

            $connection(DropTable::named($child));
            $connection(DropTable::named($parent));
        },
    );

    yield test(
        'Delete set null',
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
            $connection(Insert::into(
                $parent,
                Row::of([
                    'id' => 1,
                ]),
            ));
            $connection(Insert::into(
                $child,
                Row::of([
                    'id' => 1,
                    'parent' => 1,
                ]),
                Row::of([
                    'id' => 2,
                    'parent' => 1,
                ]),
            ));

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
        'Foreign key name',
        static function($assert) use ($connection) {
            $parent = Table\Name::of('parent_table');

            $assert->same(
                'CONSTRAINT `FK_foo` FOREIGN KEY (`parent`) REFERENCES `parent_table`(`id`)',
                ForeignKey::of(Column\Name::of('parent'), $parent, Column\Name::of('id'))
                    ->named('foo')
                    ->sql(),
            );
        },
    );

    yield test(
        'Delete join',
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
                    ->where(new class implements Comparator {
                        use Composable;

                        public function property(): string
                        {
                            return 'child.id';
                        }

                        public function sign(): Sign
                        {
                            return Sign::equality;
                        }

                        public function value(): int
                        {
                            return 2;
                        }
                    }),
            );

            $rows = $connection(Select::from($child));
            $assert->count(1, $rows);

            $rows = $connection(Select::from($parent));
            $assert->count(0, $rows);

            $connection(DropTable::named($parent->name()));
            $connection(DropTable::named($child->name()));
        },
    );

    yield test(
        'Update join',
        static function($assert) use ($connection) {
            $parent = Table\Name::of('test_join_update_parent')->as('parent');
            $child = Table\Name::of('test_join_update_child')->as('child');
            $connection(CreateTable::ifNotExists(
                $child->name(),
                Column::of(
                    Column\Name::of('id'),
                    Column\Type::int(),
                ),
                Column::of(
                    Column\Name::of('name'),
                    Column\Type::varchar(),
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
            $connection(Insert::into(
                $child->name(),
                Row::of([
                    'id' => 1,
                    'name' => 'a',
                ]),
            ));
            $connection(Insert::into(
                $parent->name(),
                Row::of([
                    'id' => 1,
                    'child' => 1,
                ]),
            ));

            $connection(
                Update::set(
                    $child,
                    new Row(new Row\Value(
                        Column\Name::of('name'),
                        'b',
                    )),
                )->join(
                    Join::left($parent)->on(
                        Column\Name::of('child')->in($parent),
                        Column\Name::of('id')->in($child),
                    ),
                ),
            );

            $rows = $connection(Select::from($child))
                ->map(static fn($row) => $row->toArray())
                ->toList();
            $assert
                ->expected([['id' => 1, 'name' => 'b']])
                ->same($rows);

            $connection(DropTable::named($parent->name()));
            $connection(DropTable::named($child->name()));
        },
    );

    yield proof(
        'Unique constraint',
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
        'PDO properties',
        Properties::any(),
        $connections,
    );

    foreach (Properties::list() as $property) {
        yield property(
            $property,
            $connections,
        )->named('PDO');
    }
};
