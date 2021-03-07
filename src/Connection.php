<?php
declare(strict_types = 1);

namespace Formal\AccessLayer;

use Formal\AccessLayer\Exception\QueryFailed;
use Innmind\Immutable\Sequence;

interface Connection
{
    /**
     * @throws QueryFailed
     *
     * @return Sequence<Row>
     */
    public function __invoke(Query $query): Sequence;
}
