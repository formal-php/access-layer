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
enum Transaction implements Builder
{
    case start;
    case commit;
    case rollback;

    #[\Override]
    public function normalize(Driver $driver): Query
    {
        return Query::of(match ($this) {
            self::start => 'START TRANSACTION',
            self::commit => 'COMMIT',
            self::rollback => 'ROLLBACK',
        });
    }
}
