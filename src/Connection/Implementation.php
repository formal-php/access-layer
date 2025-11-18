<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query,
    Row,
    Driver,
};
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
interface Implementation
{
    /**
     * @return Sequence<Row>
     */
    public function __invoke(Query|Query\Builder $query): Sequence;
    public function driver(): Driver;
}
