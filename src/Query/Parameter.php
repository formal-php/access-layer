<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

final class Parameter
{
    private ?string $name = null;
    /** @var mixed */
    private $value;
}
