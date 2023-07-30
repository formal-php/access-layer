<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Table\Column;

use Formal\AccessLayer\Table;

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
    public function __construct(string $value)
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

    public function in(Table\Name|Table\Name\Aliased $table): Name\Namespaced
    {
        return Name\Namespaced::of($this, $table);
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
    public function sql(): string
    {
        return "`{$this->value}`";
    }
}
