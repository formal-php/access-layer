<?php
declare(strict_types = 1);

use Formal\AccessLayer\{
    Query\Where,
    Query\Parameter\Type,
    Row\Value,
    Driver,
};
use Innmind\Specification\{
    Comparator\Property,
    Sign,
};
use Innmind\BlackBox\Set;
use Fixtures\Formal\AccessLayer\Table\{
    Column,
    Name,
};

return static function() {
    yield test(
        'Where no specification',
        static function($assert) {
            $where = Where::of(null);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->object($where)->instance(Where::class);
            $assert->same('', $sql);
            $assert->count(0, $parameters);
        },
    );

    yield proof(
        'Where equal comparator',
        given(
            Column::any(),
            Set\Strings::any(),
        ),
        static function($assert, $column, $value) {
            $specification = Property::of(
                $column->name()->toString(),
                Sign::equality,
                $value,
            );
            $where = Where::of($specification);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE {$column->name()->sql(Driver::mysql)} = ?",
                $sql,
            );
            $assert->count(1, $parameters);
            $assert->same($value, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
        },
    );

    yield proof(
        'Where less than comparator',
        given(
            Column::any(),
            Set\Strings::any(),
        ),
        static function($assert, $column, $value) {
            $specification = Property::of(
                $column->name()->toString(),
                Sign::lessThan,
                $value,
            );
            $where = Where::of($specification);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE {$column->name()->sql(Driver::mysql)} < ?",
                $sql,
            );
            $assert->count(1, $parameters);
            $assert->same($value, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
        },
    );

    yield proof(
        'Where less than or equal comparator',
        given(
            Column::any(),
            Set\Strings::any(),
        ),
        static function($assert, $column, $value) {
            $lessThan = Property::of(
                $column->name()->toString(),
                Sign::lessThan,
                $value,
            );
            $equal = Property::of(
                $column->name()->toString(),
                Sign::equality,
                $value,
            );
            $where = Where::of($lessThan->or($equal));

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE {$column->name()->sql(Driver::mysql)} <= ?",
                $sql,
            );
            $assert->count(1, $parameters);
            $assert->same($value, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
        },
    );

    yield proof(
        'Where less than or equal comparator on different columns',
        given(
            Column::any(),
            Column::any(),
            Set\Strings::any(),
        ),
        static function($assert, $column1, $column2, $value) {
            $lessThan = Property::of(
                $column1->name()->toString(),
                Sign::lessThan,
                $value,
            );
            $equal = Property::of(
                $column2->name()->toString(),
                Sign::equality,
                $value,
            );
            $where = Where::of($lessThan->or($equal));
            [$sql, $parameters] = $where->normalize(Driver::mysql);

            $assert->same(
                "WHERE ({$column1->name()->sql(Driver::mysql)} < ? OR {$column2->name()->sql(Driver::mysql)} = ?)",
                $sql,
            );
            $assert->count(2, $parameters);
            $assert->same($value, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
            $assert->same($value, $parameters->last()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
        },
    );

    yield proof(
        'Where more than comparator',
        given(
            Column::any(),
            Set\Strings::any(),
        ),
        static function($assert, $column, $value) {
            $specification = Property::of(
                $column->name()->toString(),
                Sign::moreThan,
                $value,
            );
            $where = Where::of($specification);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE {$column->name()->sql(Driver::mysql)} > ?",
                $sql,
            );
            $assert->count(1, $parameters);
            $assert->same($value, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
        },
    );

    yield proof(
        'Where more than or equal comparator',
        given(
            Column::any(),
            Set\Strings::any(),
        ),
        static function($assert, $column, $value) {
            $moreThan = Property::of(
                $column->name()->toString(),
                Sign::moreThan,
                $value,
            );
            $equal = Property::of(
                $column->name()->toString(),
                Sign::equality,
                $value,
            );
            $where = Where::of($moreThan->or($equal));

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE {$column->name()->sql(Driver::mysql)} >= ?",
                $sql,
            );
            $assert->count(1, $parameters);
            $assert->same($value, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
        },
    );

    yield proof(
        'Where more than or equal comparator on different columns',
        given(
            Column::any(),
            Column::any(),
            Set\Strings::any(),
        ),
        static function($assert, $column1, $column2, $value) {
            $moreThan = Property::of(
                $column1->name()->toString(),
                Sign::moreThan,
                $value,
            );
            $equal = Property::of(
                $column2->name()->toString(),
                Sign::equality,
                $value,
            );
            $where = Where::of($moreThan->or($equal));

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE ({$column1->name()->sql(Driver::mysql)} > ? OR {$column2->name()->sql(Driver::mysql)} = ?)",
                $sql,
            );
            $assert->count(2, $parameters);
            $assert->same($value, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
            $assert->same($value, $parameters->last()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
        },
    );

    yield proof(
        'Where is null comparator',
        given(Column::any()),
        static function($assert, $column) {
            $specification = Property::of(
                $column->name()->toString(),
                Sign::equality,
                null,
            );
            $where = Where::of($specification);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE {$column->name()->sql(Driver::mysql)} IS NULL",
                $sql,
            );
            $assert->count(0, $parameters);
        },
    );

    yield proof(
        'Where is not null comparator',
        given(Column::any()),
        static function($assert, $column) {
            $specification = Property::of(
                $column->name()->toString(),
                Sign::equality,
                null,
            );
            $where = Where::of($specification->not());

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE {$column->name()->sql(Driver::mysql)} IS NOT NULL",
                $sql,
            );
            $assert->count(0, $parameters);
        },
    );

    yield proof(
        'Where in comparator',
        given(
            Column::any(),
            Set\Strings::any(),
            Set\Strings::any(),
            Set\Strings::any(),
            Set\Sequence::of(
                Set\Strings::any(),
                Set\Integers::between(1, 5),
            ),
        ),
        static function($assert, $column, $value1, $value2, $value3, $values) {
            $specification = Property::of(
                $column->name()->toString(),
                Sign::in,
                [$value1, $value2, $value3],
            );
            $where = Where::of($specification);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE {$column->name()->sql(Driver::mysql)} IN (?, ?, ?)",
                $sql,
            );
            $assert->count(3, $parameters);
            $assert->same($value1, $parameters->get(0)->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
            $assert->same($value2, $parameters->get(1)->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
            $assert->same($value3, $parameters->get(2)->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));

            $specification = Property::of(
                $column->name()->toString(),
                Sign::in,
                $values,
            );
            $where = Where::of($specification);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                \count($values),
                \count_chars($sql)[63], // looking for '?' placeholders
            );
            $assert->count(\count($values), $parameters);
        },
    );

    yield proof(
        'Where not',
        given(
            Column::any(),
            Set\Strings::any(),
            Set\Strings::any(),
        ),
        static function($assert, $column, $leftValue, $rightValue){
            $specification = Property::of(
                $column->name()->toString(),
                Sign::equality,
                $leftValue,
            );
            $where = Where::of($specification->not());

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE {$column->name()->sql(Driver::mysql)} <> ?",
                $sql,
            );
            $assert->count(1, $parameters);
            $assert->same($leftValue, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));

            $left = Property::of(
                $column->name()->toString(),
                Sign::equality,
                $leftValue,
            );
            $right = Property::of(
                $column->name()->toString(),
                Sign::equality,
                $rightValue,
            );
            $specification = $left->or($right);
            $where = Where::of($specification->not());

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE NOT(({$column->name()->sql(Driver::mysql)} = ? OR {$column->name()->sql(Driver::mysql)} = ?))",
                $sql,
            );
            $assert->count(2, $parameters);
            $assert->same($leftValue, $parameters->get(0)->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
            $assert->same($rightValue, $parameters->get(1)->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
        },
    );

    yield proof(
        'Where and',
        given(
            Column::any(),
            Column::any(),
            Set\Strings::any(),
            Set\Strings::any(),
        ),
        static function($assert, $column1, $column2, $value1, $value2) {
            $left = Property::of(
                $column1->name()->toString(),
                Sign::equality,
                $value1,
            );
            $right = Property::of(
                $column2->name()->toString(),
                Sign::equality,
                $value2,
            );
            $specification = $left->and($right->not());
            $where = Where::of($specification);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE ({$column1->name()->sql(Driver::mysql)} = ? AND {$column2->name()->sql(Driver::mysql)} <> ?)",
                $sql,
            );
            $assert->count(2, $parameters);
            $assert->same($value1, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
            $assert->same($value2, $parameters->last()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));

            $left = Property::of(
                $column1->name()->toString(),
                Sign::equality,
                $value1,
            );
            $right = Property::of(
                $column2->name()->toString(),
                Sign::equality,
                $value2,
            );
            $specification = $left->not()->and($right);
            $where = Where::of($specification);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE ({$column1->name()->sql(Driver::mysql)} <> ? AND {$column2->name()->sql(Driver::mysql)} = ?)",
                $sql,
            );
            $assert->count(2, $parameters);
            $assert->same($value1, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
            $assert->same($value2, $parameters->last()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
        },
    );

    yield proof(
        'Where or',
        given(
            Column::any(),
            Column::any(),
            Set\Strings::any(),
            Set\Strings::any(),
        ),
        static function($assert, $column1, $column2, $value1, $value2) {
            $left = Property::of(
                $column1->name()->toString(),
                Sign::equality,
                $value1,
            );
            $right = Property::of(
                $column2->name()->toString(),
                Sign::equality,
                $value2,
            );
            $specification = $left->or($right->not());
            $where = Where::of($specification);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE ({$column1->name()->sql(Driver::mysql)} = ? OR {$column2->name()->sql(Driver::mysql)} <> ?)",
                $sql,
            );
            $assert->count(2, $parameters);
            $assert->same($value1, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
            $assert->same($value2, $parameters->last()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));

            $left = Property::of(
                $column1->name()->toString(),
                Sign::equality,
                $value1,
            );
            $right = Property::of(
                $column2->name()->toString(),
                Sign::equality,
                $value2,
            );
            $specification = $left->not()->or($right);
            $where = Where::of($specification);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE ({$column1->name()->sql(Driver::mysql)} <> ? OR {$column2->name()->sql(Driver::mysql)} = ?)",
                $sql,
            );
            $assert->count(2, $parameters);
            $assert->same($value1, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
            $assert->same($value2, $parameters->last()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
        },
    );

    yield proof(
        'Comparator value can be a row type',
        given(
            Column::any(),
            Column::any(),
            Set\Strings::any(),
            Set\Elements::of(
                Type::bool,
                Type::null,
                Type::int,
                Type::string,
                Type::unspecified,
            ),
        ),
        static function($assert, $column, $unused, $value, $type) {
            $specification = Property::of(
                $column->name()->toString(),
                Sign::equality,
                Value::of($unused->name(), $value, $type),
            );
            $where = Where::of($specification);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE {$column->name()->sql(Driver::mysql)} = ?",
                $sql,
            );
            $assert->count(1, $parameters);
            $assert->same($value, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
            $assert->same($type, $parameters->first()->match(
                static fn($parameter) => $parameter->type(),
                static fn() => null,
            ));
        },
    );

    yield proof(
        'Table name can be used in property',
        given(
            Name::any(),
            Column::any(),
            Set\Strings::any(),
        ),
        static function($assert, $table, $column, $value) {
            $specification = Property::of(
                $table->toString().'.'.$column->name()->toString(),
                Sign::equality,
                $value,
            );
            $where = Where::of($specification);

            [$sql, $parameters] = $where->normalize(Driver::mysql);
            $assert->same(
                "WHERE {$table->sql(Driver::mysql)}.{$column->name()->sql(Driver::mysql)} = ?",
                $sql,
            );
            $assert->count(1, $parameters);
            $assert->same($value, $parameters->first()->match(
                static fn($parameter) => $parameter->value(),
                static fn() => null,
            ));
        },
    );
};
