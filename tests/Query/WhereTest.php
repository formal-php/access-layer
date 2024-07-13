<?php
declare(strict_types = 1);

namespace Tests\Formal\AccessLayer\Query;

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
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Formal\AccessLayer\Table\{
    Column,
    Name,
};

class WhereTest extends TestCase
{
    use BlackBox;

    public function testWhereNoSpecification()
    {
        $where = Where::of(null);

        $this->assertInstanceOf(Where::class, $where);
        $this->assertSame('', $where->sql(Driver::mysql));
        $this->assertCount(0, $where->parameters());
    }

    public function testWhereEqualComparator()
    {
        $this
            ->forAll(Column::any(), Set\Strings::any())
            ->then(function($column, $value) {
                $specification = Property::of(
                    $column->name()->toString(),
                    Sign::equality,
                    $value,
                );
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$column->name()->sql(Driver::mysql)} = ?",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(1, $where->parameters());
                $this->assertSame($value, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
    }

    public function testWhereLessThanComparator()
    {
        $this
            ->forAll(Column::any(), Set\Strings::any())
            ->then(function($column, $value) {
                $specification = Property::of(
                    $column->name()->toString(),
                    Sign::lessThan,
                    $value,
                );
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$column->name()->sql(Driver::mysql)} < ?",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(1, $where->parameters());
                $this->assertSame($value, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
    }

    public function testWhereLessThanOrEqualComparator()
    {
        $this
            ->forAll(Column::any(), Set\Strings::any())
            ->then(function($column, $value) {
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

                $this->assertSame(
                    "WHERE {$column->name()->sql(Driver::mysql)} <= ?",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(1, $where->parameters());
                $this->assertSame($value, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
    }

    public function testWhereMoreThanComparator()
    {
        $this
            ->forAll(Column::any(), Set\Strings::any())
            ->then(function($column, $value) {
                $specification = Property::of(
                    $column->name()->toString(),
                    Sign::moreThan,
                    $value,
                );
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$column->name()->sql(Driver::mysql)} > ?",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(1, $where->parameters());
                $this->assertSame($value, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
    }

    public function testWhereMoreThanOrEqualComparator()
    {
        $this
            ->forAll(Column::any(), Set\Strings::any())
            ->then(function($column, $value) {
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

                $this->assertSame(
                    "WHERE {$column->name()->sql(Driver::mysql)} >= ?",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(1, $where->parameters());
                $this->assertSame($value, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
    }

    public function testWhereIsNullComparator()
    {
        $this
            ->forAll(Column::any())
            ->then(function($column) {
                $specification = Property::of(
                    $column->name()->toString(),
                    Sign::equality,
                    null,
                );
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$column->name()->sql(Driver::mysql)} IS NULL",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(0, $where->parameters());
            });
    }

    public function testWhereIsNotNullComparator()
    {
        $this
            ->forAll(Column::any())
            ->then(function($column) {
                $specification = Property::of(
                    $column->name()->toString(),
                    Sign::equality,
                    null,
                );
                $where = Where::of($specification->not());

                $this->assertSame(
                    "WHERE {$column->name()->sql(Driver::mysql)} IS NOT NULL",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(0, $where->parameters());
            });
    }

    public function testWhereInComparator()
    {
        $this
            ->forAll(
                Column::any(),
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($column, $value1, $value2, $value3) {
                $specification = Property::of(
                    $column->name()->toString(),
                    Sign::in,
                    [$value1, $value2, $value3],
                );
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$column->name()->sql(Driver::mysql)} IN (?, ?, ?)",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(3, $where->parameters());
                $this->assertSame($value1, $where->parameters()->get(0)->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
                $this->assertSame($value2, $where->parameters()->get(1)->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
                $this->assertSame($value3, $where->parameters()->get(2)->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
        $this
            ->forAll(
                Column::any(),
                Set\Sequence::of(
                    Set\Strings::any(),
                    Set\Integers::between(1, 5),
                ),
            )
            ->then(function($column, $values) {
                $specification = Property::of(
                    $column->name()->toString(),
                    Sign::in,
                    $values,
                );
                $where = Where::of($specification);

                $this->assertSame(
                    \count($values),
                    \count_chars($where->sql(Driver::mysql))[63], // looking for '?' placeholders
                );
                $this->assertCount(\count($values), $where->parameters());
            });
    }

    public function testWhereNot()
    {
        $this
            ->forAll(Column::any(), Set\Strings::any())
            ->then(function($column, $value) {
                $specification = Property::of(
                    $column->name()->toString(),
                    Sign::equality,
                    $value,
                );
                $where = Where::of($specification->not());

                $this->assertSame(
                    "WHERE {$column->name()->sql(Driver::mysql)} <> ?",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(1, $where->parameters());
                $this->assertSame($value, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
        $this
            ->forAll(
                Column::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($column, $leftValue, $rightValue) {
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

                $this->assertSame(
                    "WHERE NOT(({$column->name()->sql(Driver::mysql)} = ? OR {$column->name()->sql(Driver::mysql)} = ?))",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(2, $where->parameters());
                $this->assertSame($leftValue, $where->parameters()->get(0)->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
                $this->assertSame($rightValue, $where->parameters()->get(1)->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
    }

    public function testWhereAnd()
    {
        $this
            ->forAll(
                Column::any(),
                Column::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($column1, $column2, $value1, $value2) {
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

                $this->assertSame(
                    "WHERE ({$column1->name()->sql(Driver::mysql)} = ? AND {$column2->name()->sql(Driver::mysql)} <> ?)",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(2, $where->parameters());
                $this->assertSame($value1, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
                $this->assertSame($value2, $where->parameters()->last()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
        $this
            ->forAll(
                Column::any(),
                Column::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($column1, $column2, $value1, $value2) {
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

                $this->assertSame(
                    "WHERE ({$column1->name()->sql(Driver::mysql)} <> ? AND {$column2->name()->sql(Driver::mysql)} = ?)",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(2, $where->parameters());
                $this->assertSame($value1, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
                $this->assertSame($value2, $where->parameters()->last()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
    }

    public function testWhereOr()
    {
        $this
            ->forAll(
                Column::any(),
                Column::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($column1, $column2, $value1, $value2) {
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

                $this->assertSame(
                    "WHERE ({$column1->name()->sql(Driver::mysql)} = ? OR {$column2->name()->sql(Driver::mysql)} <> ?)",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(2, $where->parameters());
                $this->assertSame($value1, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
                $this->assertSame($value2, $where->parameters()->last()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
        $this
            ->forAll(
                Column::any(),
                Column::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($column1, $column2, $value1, $value2) {
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

                $this->assertSame(
                    "WHERE ({$column1->name()->sql(Driver::mysql)} <> ? OR {$column2->name()->sql(Driver::mysql)} = ?)",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(2, $where->parameters());
                $this->assertSame($value1, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
                $this->assertSame($value2, $where->parameters()->last()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
    }

    public function testComparatorValueCanBeARowType()
    {
        $this
            ->forAll(
                Column::any(),
                Column::any(),
                Set\Strings::any(),
                $this->type(),
            )
            ->then(function($column, $unused, $value, $type) {
                $specification = Property::of(
                    $column->name()->toString(),
                    Sign::equality,
                    new Value($unused->name(), $value, $type),
                );
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$column->name()->sql(Driver::mysql)} = ?",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(1, $where->parameters());
                $this->assertSame($value, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
                $this->assertSame($type, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->type(),
                    static fn() => null,
                ));
            });
    }

    public function testTableNameCanBeUsedInProperty()
    {
        $this
            ->forAll(
                Name::any(),
                Column::any(),
                Set\Strings::any(),
            )
            ->then(function($table, $column, $value) {
                $specification = Property::of(
                    $table->toString().'.'.$column->name()->toString(),
                    Sign::equality,
                    $value,
                );
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$table->sql(Driver::mysql)}.{$column->name()->sql(Driver::mysql)} = ?",
                    $where->sql(Driver::mysql),
                );
                $this->assertCount(1, $where->parameters());
                $this->assertSame($value, $where->parameters()->first()->match(
                    static fn($parameter) => $parameter->value(),
                    static fn() => null,
                ));
            });
    }

    private function type(): Set
    {
        return Set\Elements::of(
            Type::bool,
            Type::null,
            Type::int,
            Type::string,
            Type::unspecified,
        );
    }
}
