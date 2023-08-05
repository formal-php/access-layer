<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query\Select;

/**
 * @psalm-immutable
 */
enum Direction
{
    case asc;
    case desc;

    /**
     * @return non-empty-string
     */
    public function sql(): string
    {
        return \strtoupper($this->name);
    }
}
