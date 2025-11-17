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
final class Commit implements Builder
{
    #[\Override]
    public function normalize(Driver $driver): Query
    {
        return Query::of('COMMIT');
    }
}
