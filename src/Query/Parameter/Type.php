<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query\Parameter;

/**
 * @psalm-immutable
 */
enum Type
{
    case bool;
    case null;
    case int;
    case string;
    case unspecified;
}
