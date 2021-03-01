<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query\Parameter;

final class Type
{
    private static ?self $bool = null;
    private static ?self $null = null;
    private static ?self $int = null;
    private static ?self $string = null;
    private static ?self $unspecified = null;

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function bool(): self
    {
        return self::$bool ?? self::$bool = new self('bool');
    }

    public static function null(): self
    {
        return self::$null ?? self::$null = new self('null');
    }

    public static function int(): self
    {
        return self::$int ?? self::$int = new self('int');
    }

    public static function string(): self
    {
        return self::$string ?? self::$string = new self('string');
    }

    public static function unspecified(): self
    {
        return self::$unspecified ?? self::$unspecified = new self('unspecified');
    }

    public function equals(self $type): bool
    {
        return $type === $this;
    }
}
