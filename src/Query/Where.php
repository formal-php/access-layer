<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Table\Name,
    Table\Column,
    Row\Value,
};
use Innmind\Specification\{
    Specification,
    Comparator,
    Composite,
    Not,
    Sign,
    Operator,
};
use Innmind\Immutable\{
    Sequence,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Where
{
    private ?Specification $specification;

    private function __construct(?Specification $specification)
    {
        $this->specification = $specification;
    }

    /**
     * @psalm-pure
     */
    public static function of(?Specification $specification): self
    {
        return new self($specification);
    }

    /**
     * @psalm-pure
     */
    public static function everything(): self
    {
        return new self(null);
    }

    /**
     * @return Sequence<Parameter>
     */
    public function parameters(): Sequence
    {
        /** @var Sequence<Parameter> */
        $parameters = Sequence::of();

        if (\is_null($this->specification)) {
            return $parameters;
        }

        return $this->findParamaters(
            $parameters,
            $this->specification,
        );
    }

    public function sql(): string
    {
        if (\is_null($this->specification)) {
            return '';
        }

        return \sprintf(
            'WHERE %s',
            $this->buildSql($this->specification),
        );
    }

    private function buildSql(Specification $specification): string
    {
        return match (true) {
            $specification instanceof Comparator => $this->buildComparator($specification),
            $specification instanceof Composite => $this->buildComposite($specification),
            $specification instanceof Not => $this->negate($specification),
        };
    }

    private function buildComparator(Comparator $specification): string
    {
        $column = $this->buildColumn($specification);
        $sign = match ($specification->sign()) {
            Sign::equality => '=',
            Sign::inequality => '<>',
            Sign::lessThan => '<',
            Sign::moreThan => '>',
            Sign::lessThanOrEqual => '<=',
            Sign::moreThanOrEqual => '>=',
            Sign::isNull => 'IS NULL',
            Sign::isNotNull => 'IS NOT NULL',
            Sign::startsWith => 'LIKE',
            Sign::endsWith => 'LIKE',
            Sign::contains => 'LIKE',
            Sign::in => 'IN',
        };

        return match ($specification->sign()) {
            Sign::isNull => \sprintf('%s %s', $column, $sign),
            Sign::isNotNull => \sprintf('%s %s', $column, $sign),
            Sign::in => $this->buildInSql($specification),
            default => \sprintf(
                '%s %s ?',
                $column,
                $sign,
            ),
        };
    }

    private function buildComposite(Composite $specification): string
    {
        return \sprintf(
            '(%s %s %s)',
            $this->buildSql($specification->left()),
            $specification->operator() === Operator::and ? 'AND' : 'OR',
            $this->buildSql($specification->right()),
        );
    }

    private function negate(Not $specification): string
    {
        return \sprintf(
            'NOT(%s)',
            $this->buildSql($specification->specification()),
        );
    }

    private function buildInSql(Comparator $specification): string
    {
        /** @var array */
        $value = $this->value($specification);
        $placeholders = \array_map(
            static fn($_) => '?',
            $value,
        );

        return \sprintf(
            '%s IN (%s)',
            (new Column\Name($specification->property()))->sql(),
            \implode(', ', $placeholders),
        );
    }

    /**
     * @param Sequence<Parameter> $parameters
     *
     * @return Sequence<Parameter>
     */
    private function findParamaters(
        Sequence $parameters,
        Specification $specification,
    ): Sequence {
        return match (true) {
            $specification instanceof Not => $this->findParamaters(
                $parameters,
                $specification->specification(),
            ),
            $specification instanceof Composite => $this->findParamaters(
                $parameters,
                $specification->left(),
            )->append($this->findParamaters(
                $parameters,
                $specification->right(),
            )),
            $specification instanceof Comparator => $this->findComparatorParameters(
                $parameters,
                $specification,
            ),
        };
    }

    /**
     * @param Sequence<Parameter> $parameters
     *
     * @return Sequence<Parameter>
     */
    private function findComparatorParameters(
        Sequence $parameters,
        Comparator $specification,
    ): Sequence {
        if (
            $specification->sign() === Sign::isNull ||
            $specification->sign() === Sign::isNotNull
        ) {
            return $parameters;
        }

        /** @var mixed */
        $value = $this->value($specification);
        $type = $this->type($specification);

        if ($specification->sign() === Sign::in) {
            /**
             * @var mixed $in
             */
            foreach ($value as $in) {
                $parameters = ($parameters)(Parameter::of($in, $type));
            }

            return $parameters;
        }

        return ($parameters)(Parameter::of($value, $type));
    }

    private function value(Comparator $specification): mixed
    {
        /** @var mixed */
        $value = $specification->value();

        if ($value instanceof Value) {
            /** @var mixed */
            $value = $value->value();
        }

        return match ($specification->sign()) {
            Sign::startsWith => "%$value",
            Sign::endsWith => "$value%",
            Sign::contains => "%$value%",
            default => $value,
        };
    }

    private function type(Comparator $specification): ?Parameter\Type
    {
        if ($specification->value() instanceof Value) {
            return $specification->value()->type();
        }

        return null;
    }

    private function buildColumn(Comparator $specification): string
    {
        $property = Str::of($specification->property());

        $parts = $property->split('.');
        $table = $parts
            ->first()
            ->map(static fn($name) => $name->toString())
            ->map(static fn($name) => new Name($name));
        $column = $parts
            ->get(1)
            ->map(static fn($name) => $name->toString())
            ->map(static fn($name) => new Column\Name($name));

        return Maybe::all($table, $column)
            ->map(static fn(Name $table, Column\Name $column) => "{$table->sql()}.{$column->sql()}")
            ->match(
                static fn($withTable) => $withTable,
                static fn() => (new Column\Name($specification->property()))->sql(),
            );
    }
}
