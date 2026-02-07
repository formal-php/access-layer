<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Driver,
};

/**
 * @psalm-immutable
 */
interface Builder
{
    public function normalize(Driver $driver): Query;
}
