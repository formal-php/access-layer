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

    /**
     * @psalm-pure
     */
    public static function for(mixed $value): self
    {
        return match (true) {
            \is_bool($value) => self::bool,
            \is_null($value) => self::null,
            \is_int($value) => self::int,
            \is_string($value) => self::string,
            default => self::unspecified,
        };
    }
}
