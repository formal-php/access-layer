<?php
declare(strict_types = 1);

namespace Formal\AccessLayer;

use Formal\AccessLayer\Query\Parameter;
use Innmind\Immutable\Sequence;

interface Query
{
    /**
     * @return Sequence<Parameter>
     */
    public function parameters(): Sequence;
    public function sql(): string;
}
