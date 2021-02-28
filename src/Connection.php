<?php
declare(strict_types = 1);

namespace Formal\AccessLayer;

use Innmind\Immutable\Sequence;

interface Connection
{
    /**
     * @return Sequence<Row>
     */
    public function __invoke(Query $query): Sequence;
}
