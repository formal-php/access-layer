<?php
declare(strict_types = 1);

namespace Formal\AccessLayer;

use Formal\AccessLayer\{
    Row\Value,
    Table\Column,
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
     * @param Sequence<Value> $values
     */
    private function __construct(Sequence $values)
    {
        $this->values = $values;
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     */
    public static function new(Value ...$values): self
    {
        return new self(Sequence::of(...$values));
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

            /** @psalm-suppress RedundantCastGivenDocblockType Because PHP automatically cast a numeric string */
            $values[] = Value::of(Column\Name::of((string) $key), $value);
        }

        return self::new(...$values);
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
     * @return Sequence<Value>
     */
    public function values(): Sequence
    {
        return $this->values;
    }

    public function toArray(): array
    {
        return $this->values->reduce(
            [],
            static function(array $values, $value) {
                /** @psalm-suppress MixedAssignment */
                $values[$value->column()->toString()] = $value->value();

                return $values;
            },
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
