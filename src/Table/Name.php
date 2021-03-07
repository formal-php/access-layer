<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Table;

use Formal\AccessLayer\Exception\DomainException;

final class Name
{
    private string $value;

    public function __construct(string $value)
    {
        if ($value === '') {
            throw new DomainException($value);
        }

        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function sql(): string
    {
        return "`{$this->value}`";
    }
}
