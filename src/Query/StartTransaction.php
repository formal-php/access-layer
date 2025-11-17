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
final class StartTransaction implements Builder
{
    #[\Override]
    public function normalize(Driver $driver): Query
    {
        return SQL::of('START TRANSACTION');
    }
}
