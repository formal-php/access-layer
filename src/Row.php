<?php
declare(strict_types = 1);

namespace Formal\AccessLayer;

use Formal\AccessLayer\{
    Row\Value,
    Table\Column,
    Query\Parameter\Type,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Row
{
    /** @var Sequence<Value> */
    private Sequence $values;

    /**
     * @no-named-arguments
     */
    public function __construct(Value ...$values)
    {
        $this->values = Sequence::of(...$values);
    }

    /**
     * @psalm-pure
     *
     * @param array<string, mixed> $columns
     */
    public static function of(array $columns): self
    {
        /** @var list<Value> */
        $values = [];

        /**
         * @var mixed $value
         */
        foreach ($columns as $key => $value) {
            if ($key === '') {
                continue;
            }

            $values[] = new Value(new Column\Name($key), $value);
        }

        return new self(...$values);
    }

    public function contains(string $name): bool
    {
        return $this->values->any($this->match($name));
    }

    /**
     * @return Maybe<mixed>
     */
    public function column(string $name): Maybe
    {
        return $this
            ->values
            ->find($this->match($name))
            ->map(static fn($value): mixed => $value->value());
    }

    /**
     * The order of provided columns is always the same
     *
     * @template T
     *
     * @param T $carry
     * @param callable(T, Column\Name, mixed, Type): T $reducer
     *
     * @return T
     */
    public function reduce(mixed $carry, callable $reducer): mixed
    {
        /** @psalm-suppress MixedArgument */
        return $this->values->reduce(
            $carry,
            static fn(mixed $carry, Value $value) => $reducer(
                $carry,
                $value->column(),
                $value->value(),
                $value->type(),
            ),
        );
    }

    /**
     * @return callable(Value): bool
     */
    private function match(string $column): callable
    {
        return static fn($value) => $value->column()->toString() === $column;
    }
}
