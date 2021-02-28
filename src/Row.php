<?php
declare(strict_types = 1);

namespace Formal\AccessLayer;

final class Row
{
    /** @var array<string, mixed> */
    private array $columns;

    /**
     * @param array<string, mixed> $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }
}
