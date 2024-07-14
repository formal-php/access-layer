<?php
declare(strict_types = 1);

namespace Formal\AccessLayer;

/**
 * @psalm-immutable
 */
enum Driver
{
    case mysql;
    case postgres;

    /**
     * @param non-empty-string $name
     *
     * @return non-empty-string
     */
    public function escapeName(string $name): string
    {
        return match ($this) {
            self::mysql => "`$name`",
            self::postgres => "\"$name\"",
        };
    }
}
