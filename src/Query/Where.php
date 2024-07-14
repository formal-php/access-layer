<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Table\Name,
    Table\Column,
    Row\Value,
    Query,
    Driver,
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

    public function sql(Driver $driver): string
    {
        if (\is_null($this->specification)) {
            return '';
        }

        return \sprintf(
            'WHERE %s',
            $this->buildSql($driver, $this->specification),
        );
    }

    private function buildSql(
        Driver $driver,
        Specification $specification,
    ): string {
        return match (true) {
            $specification instanceof Comparator => $this->buildComparator($driver, $specification),
            $specification instanceof Composite => $this->buildComposite($driver, $specification),
            $specification instanceof Not => $this->negate($driver, $specification),
        };
    }

    private function buildComparator(
        Driver $driver,
        Comparator $specification,
    ): string {
        $column = $this->buildColumn($driver, $specification);
        $sign = match ($specification->sign()) {
            Sign::equality => '=',
            Sign::lessThan => '<',
            Sign::moreThan => '>',
            Sign::startsWith => 'LIKE',
            Sign::endsWith => 'LIKE',
            Sign::contains => 'LIKE',
            Sign::in => 'IN',
        };

        if ($specification->sign() === Sign::equality && \is_null($specification->value())) {
            return \sprintf('%s IS NULL', $column);
        }

        $comparator = match ($specification->sign()) {
            Sign::in => $this->buildInSql($driver, $specification),
            default => \sprintf(
                '%s %s ?',
                $column,
                $sign,
            ),
        };

        if (
            $driver === Driver::sqlite &&
            \in_array(
                $specification->sign(),
                [Sign::startsWith, Sign::endsWith, Sign::contains],
                true,
            )
        ) {
            $comparator .= " ESCAPE '\\'";
        }

        return $comparator;
    }

    private function buildComposite(
        Driver $driver,
        Composite $specification,
    ): string {
        if (
            $specification->operator() === Operator::or &&
            $specification->left() instanceof Comparator &&
            $specification->left()->sign() === Sign::moreThan &&
            $specification->right() instanceof Comparator &&
            $specification->right()->sign() === Sign::equality &&
            $specification->left()->property() === $specification->right()->property() &&
            $specification->left()->value() === $specification->right()->value()
        ) {
            return \sprintf(
                '%s >= ?',
                $this->buildColumn($driver, $specification->left()),
            );
        }

        if (
            $specification->operator() === Operator::or &&
            $specification->left() instanceof Comparator &&
            $specification->left()->sign() === Sign::lessThan &&
            $specification->right() instanceof Comparator &&
            $specification->right()->sign() === Sign::equality &&
            $specification->left()->property() === $specification->right()->property() &&
            $specification->left()->value() === $specification->right()->value()
        ) {
            return \sprintf(
                '%s <= ?',
                $this->buildColumn($driver, $specification->left()),
            );
        }

        return \sprintf(
            '(%s %s %s)',
            $this->buildSql($driver, $specification->left()),
            $specification->operator() === Operator::and ? 'AND' : 'OR',
            $this->buildSql($driver, $specification->right()),
        );
    }

    private function negate(Driver $driver, Not $specification): string
    {
        $inner = $specification->specification();

        if (
            $inner instanceof Comparator &&
            $inner->sign() === Sign::equality &&
            \is_null($inner->value())
        ) {
            return \sprintf(
                '%s IS NOT NULL',
                $this->buildColumn($driver, $inner),
            );
        }

        if (
            $inner instanceof Comparator &&
            $inner->sign() === Sign::equality
        ) {
            return \sprintf(
                '%s <> ?',
                $this->buildColumn($driver, $inner),
            );
        }

        return \sprintf(
            'NOT(%s)',
            $this->buildSql($driver, $inner),
        );
    }

    private function buildInSql(
        Driver $driver,
        Comparator $specification,
    ): string {
        if ($specification->value() instanceof Query) {
            return \sprintf(
                '%s IN (%s)',
                $this->buildColumn($driver, $specification),
                $specification->value()->sql($driver),
            );
        }

        /** @var array */
        $value = $this->value($specification);
        $placeholders = \array_map(
            static fn($_) => '?',
            $value,
        );

        return \sprintf(
            '%s IN (%s)',
            $this->buildColumn($driver, $specification),
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
        if (
            $specification instanceof Composite &&
            $specification->operator() === Operator::or &&
            $specification->left() instanceof Comparator &&
            $specification->left()->sign() === Sign::moreThan &&
            $specification->right() instanceof Comparator &&
            $specification->right()->sign() === Sign::equality &&
            $specification->left()->property() === $specification->right()->property() &&
            $specification->left()->value() === $specification->right()->value()
        ) {
            return $this->findComparatorParameters(
                $parameters,
                $specification->left(),
            );
        }

        if (
            $specification instanceof Composite &&
            $specification->operator() === Operator::or &&
            $specification->left() instanceof Comparator &&
            $specification->left()->sign() === Sign::lessThan &&
            $specification->right() instanceof Comparator &&
            $specification->right()->sign() === Sign::equality &&
            $specification->left()->property() === $specification->right()->property() &&
            $specification->left()->value() === $specification->right()->value()
        ) {
            return $this->findComparatorParameters(
                $parameters,
                $specification->left(),
            );
        }

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
            $specification->sign() === Sign::equality &&
            \is_null($specification->value())
        ) {
            return $parameters;
        }

        /** @var mixed */
        $value = $this->value($specification);
        $type = $this->type($specification);

        if ($specification->sign() === Sign::in) {
            if ($specification->value() instanceof Query) {
                return $parameters->append($specification->value()->parameters());
            }

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

        // Blackslash, underscore and percentage are special characters in a
        // LIKE condition in order to build patterns, they are escaped here so
        // the user can use these characters for an exact match as would suggest
        // the Sign name.
        // If you land here because your pattern doesn't work, know that you
        // can't achieve this with a specification, you'll need to build the SQL
        // query yourself.
        return match ($specification->sign()) {
            Sign::startsWith => Str::of((string) $value)
                ->replace('\\', '\\\\')
                ->replace('_', '\_')
                ->replace('%', '\%')
                ->append('%')
                ->toString(),
            Sign::endsWith => Str::of((string) $value)
                ->replace('\\', '\\\\')
                ->replace('_', '\_')
                ->replace('%', '\%')
                ->prepend('%')
                ->toString(),
            Sign::contains => Str::of((string) $value)
                ->replace('\\', '\\\\')
                ->replace('_', '\_')
                ->replace('%', '\%')
                ->append('%')
                ->prepend('%')
                ->toString(),
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

    private function buildColumn(
        Driver $driver,
        Comparator $specification,
    ): string {
        $property = Str::of($specification->property());

        $parts = $property->split('.');
        /** @psalm-suppress ArgumentTypeCoercion */
        $table = $parts
            ->first()
            ->filter(static fn($name) => !$name->empty())
            ->map(static fn($name) => $name->toString())
            ->map(Name::of(...));
        /** @psalm-suppress ArgumentTypeCoercion */
        $column = $parts
            ->get(1)
            ->filter(static fn($name) => !$name->empty())
            ->map(static fn($name) => $name->toString())
            ->map(Column\Name::of(...));

        return Maybe::all($table, $column)
            ->map(static fn(Name $table, Column\Name $column) => "{$table->sql($driver)}.{$column->sql($driver)}")
            ->match(
                static fn($withTable) => $withTable,
                static fn() => Column\Name::of($specification->property())->sql($driver),
            );
    }
}
