<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Table;

use Formal\AccessLayer\Driver;

/**
 * @psalm-immutable
 */
final class Name
{
    /** @var non-empty-string */
    private string $value;

    /**
     * @param non-empty-string $value
     */
    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $value
     */
    public static function of(string $value): self
    {
        return new self($value);
    }

    /**
     * @param non-empty-string $alias
     */
    public function as(string $alias): Name\Aliased
    {
        return Name\Aliased::of($this, $alias);
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @return non-empty-string
     */
    public function sql(Driver $driver): string
    {
        return $driver->escapeName($this->value);
    }
}
