<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Query,
    Row,
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
    public function __invoke(Query $query): Sequence;
}
