<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\Query;

/**
 * @psalm-immutable
 */
interface Builder
{
    public function normalize(): Query;
}
