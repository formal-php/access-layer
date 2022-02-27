<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Table;

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
