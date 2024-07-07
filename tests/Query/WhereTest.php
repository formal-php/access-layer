<?php
declare(strict_types = 1);

namespace Tests\Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query\Where,
    Query\Parameter\Type,
    Row\Value,
};
use Innmind\Specification\{
    Comparator,
    Composite,
    Not,
    Operator,
    OrSpecification,
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
        $this->assertSame('', $where->sql());
        $this->assertCount(0, $where->parameters());
    }

    public function testWhereEqualComparator()
    {
        $this
            ->forAll(Column::any(), Set\Strings::any())
            ->then(function($column, $value) {
                $specification = $this->createMock(Comparator::class);
                $specification
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $specification
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $specification
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value);
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$column->name()->sql()} = ?",
                    $where->sql(),
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
                $specification = $this->createMock(Comparator::class);
                $specification
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $specification
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::lessThan);
                $specification
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value);
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$column->name()->sql()} < ?",
                    $where->sql(),
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
                $lessThan = $this->createMock(Comparator::class);
                $lessThan
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $lessThan
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::lessThan);
                $lessThan
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value);
                $equal = $this->createMock(Comparator::class);
                $equal
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $equal
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $equal
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value);
                $where = Where::of(new OrSpecification($lessThan, $equal));

                $this->assertSame(
                    "WHERE {$column->name()->sql()} <= ?",
                    $where->sql(),
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
                $specification = $this->createMock(Comparator::class);
                $specification
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $specification
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::moreThan);
                $specification
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value);
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$column->name()->sql()} > ?",
                    $where->sql(),
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
                $moreThan = $this->createMock(Comparator::class);
                $moreThan
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $moreThan
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::moreThan);
                $moreThan
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value);
                $equal = $this->createMock(Comparator::class);
                $equal
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $equal
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $equal
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value);
                $where = Where::of(new OrSpecification($moreThan, $equal));

                $this->assertSame(
                    "WHERE {$column->name()->sql()} >= ?",
                    $where->sql(),
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
                $specification = $this->createMock(Comparator::class);
                $specification
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $specification
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $specification
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn(null);
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$column->name()->sql()} IS NULL",
                    $where->sql(),
                );
                $this->assertCount(0, $where->parameters());
            });
    }

    public function testWhereIsNotNullComparator()
    {
        $this
            ->forAll(Column::any())
            ->then(function($column) {
                $specification = $this->createMock(Comparator::class);
                $specification
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $specification
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $specification
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn(null);
                $where = Where::of(new Not\Implementation($specification));

                $this->assertSame(
                    "WHERE {$column->name()->sql()} IS NOT NULL",
                    $where->sql(),
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
                $specification = $this->createMock(Comparator::class);
                $specification
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $specification
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::in);
                $specification
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn([$value1, $value2, $value3]);
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$column->name()->sql()} IN (?, ?, ?)",
                    $where->sql(),
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
                $specification = $this->createMock(Comparator::class);
                $specification
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $specification
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::in);
                $specification
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($values);
                $where = Where::of($specification);

                $this->assertSame(
                    \count($values),
                    \count_chars($where->sql())[63], // looking for '?' placeholders
                );
                $this->assertCount(\count($values), $where->parameters());
            });
    }

    public function testWhereNot()
    {
        $this
            ->forAll(Column::any(), Set\Strings::any())
            ->then(function($column, $value) {
                $specification = $this->createMock(Comparator::class);
                $specification
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $specification
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $specification
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value);
                $not = $this->createMock(Not::class);
                $not
                    ->expects($this->any())
                    ->method('specification')
                    ->willReturn($specification);
                $where = Where::of($not);

                $this->assertSame(
                    "WHERE {$column->name()->sql()} <> ?",
                    $where->sql(),
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
                $left = $this->createMock(Comparator::class);
                $left
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $left
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $left
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($leftValue);
                $right = $this->createMock(Comparator::class);
                $right
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $right
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $right
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($rightValue);
                $specification = new OrSpecification($left, $right);
                $where = Where::of(new Not\Implementation($specification));

                $this->assertSame(
                    "WHERE NOT(({$column->name()->sql()} = ? OR {$column->name()->sql()} = ?))",
                    $where->sql(),
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
                $left = $this->createMock(Comparator::class);
                $left
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column1->name()->toString());
                $left
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $left
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value1);
                $right = $this->createMock(Comparator::class);
                $right
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column2->name()->toString());
                $right
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $right
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value2);
                $specification = $this->createMock(Composite::class);
                $specification
                    ->expects($this->any())
                    ->method('left')
                    ->willReturn($left);
                $specification
                    ->expects($this->any())
                    ->method('right')
                    ->willReturn(new Not\Implementation($right));
                $specification
                    ->expects($this->any())
                    ->method('operator')
                    ->willReturn(Operator::and);
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE ({$column1->name()->sql()} = ? AND {$column2->name()->sql()} <> ?)",
                    $where->sql(),
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
                $left = $this->createMock(Comparator::class);
                $left
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column1->name()->toString());
                $left
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $left
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value1);
                $right = $this->createMock(Comparator::class);
                $right
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column2->name()->toString());
                $right
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $right
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value2);
                $specification = $this->createMock(Composite::class);
                $specification
                    ->expects($this->any())
                    ->method('left')
                    ->willReturn(new Not\Implementation($left));
                $specification
                    ->expects($this->any())
                    ->method('right')
                    ->willReturn($right);
                $specification
                    ->expects($this->any())
                    ->method('operator')
                    ->willReturn(Operator::and);
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE ({$column1->name()->sql()} <> ? AND {$column2->name()->sql()} = ?)",
                    $where->sql(),
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
                $left = $this->createMock(Comparator::class);
                $left
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column1->name()->toString());
                $left
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $left
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value1);
                $right = $this->createMock(Comparator::class);
                $right
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column2->name()->toString());
                $right
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $right
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value2);
                $specification = $this->createMock(Composite::class);
                $specification
                    ->expects($this->any())
                    ->method('left')
                    ->willReturn($left);
                $specification
                    ->expects($this->any())
                    ->method('right')
                    ->willReturn(new Not\Implementation($right));
                $specification
                    ->expects($this->any())
                    ->method('operator')
                    ->willReturn(Operator::or);
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE ({$column1->name()->sql()} = ? OR {$column2->name()->sql()} <> ?)",
                    $where->sql(),
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
                $left = $this->createMock(Comparator::class);
                $left
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column1->name()->toString());
                $left
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $left
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value1);
                $right = $this->createMock(Comparator::class);
                $right
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column2->name()->toString());
                $right
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $right
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value2);
                $specification = $this->createMock(Composite::class);
                $specification
                    ->expects($this->any())
                    ->method('left')
                    ->willReturn(new Not\Implementation($left));
                $specification
                    ->expects($this->any())
                    ->method('right')
                    ->willReturn($right);
                $specification
                    ->expects($this->any())
                    ->method('operator')
                    ->willReturn(Operator::or);
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE ({$column1->name()->sql()} <> ? OR {$column2->name()->sql()} = ?)",
                    $where->sql(),
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
                $specification = $this->createMock(Comparator::class);
                $specification
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($column->name()->toString());
                $specification
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $specification
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn(new Value($unused->name(), $value, $type));
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$column->name()->sql()} = ?",
                    $where->sql(),
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
                $specification = $this->createMock(Comparator::class);
                $specification
                    ->expects($this->any())
                    ->method('property')
                    ->willReturn($table->toString().'.'.$column->name()->toString());
                $specification
                    ->expects($this->any())
                    ->method('sign')
                    ->willReturn(Sign::equality);
                $specification
                    ->expects($this->any())
                    ->method('value')
                    ->willReturn($value);
                $where = Where::of($specification);

                $this->assertSame(
                    "WHERE {$table->sql()}.{$column->name()->sql()} = ?",
                    $where->sql(),
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
