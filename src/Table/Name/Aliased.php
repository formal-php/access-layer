<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Table\Name;

use Formal\AccessLayer\{
    Table\Name,
    Driver,
};

/**
 * @psalm-immutable
 */
final class Aliased
{
    private Name $name;
    /** @var non-empty-string */
    private string $alias;

    /**
     * @param non-empty-string $alias
     */
    private function __construct(Name $name, string $alias)
    {
        $this->name = $name;
        $this->alias = $alias;
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $alias
     */
    public static function of(Name $name, string $alias): self
    {
        return new self($name, $alias);
    }

    public function name(): Name
    {
        return $this->name;
    }

    /**
     * @return non-empty-string
     */
    public function alias(): string
    {
        return $this->alias;
    }

    /**
     * @return non-empty-string
     */
    public function sql(Driver $driver): string
    {
        $alias = $driver->escapeName($this->alias);

        return "{$this->name->sql($driver)} AS $alias";
    }
}
