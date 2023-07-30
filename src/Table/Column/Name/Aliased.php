<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Table\Column\Name;

use Formal\AccessLayer\Table\Column\Name;

/**
 * @psalm-immutable
 */
final class Aliased
{
    private Name|Namespaced $name;
    /** @var non-empty-string */
    private string $alias;

    /**
     * @param non-empty-string $alias
     */
    private function __construct(Name|Namespaced $name, string $alias)
    {
        $this->name = $name;
        $this->alias = $alias;
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $alias
     */
    public static function of(Name|Namespaced $name, string $alias): self
    {
        return new self($name, $alias);
    }

    public function name(): Name|Namespaced
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
    public function sql(): string
    {
        return "{$this->name->sql()} AS `{$this->alias}`";
    }
}
