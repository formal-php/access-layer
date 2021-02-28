<?php
declare(strict_types = 1);

namespace Formal\AccessLayer;

use Innmind\Immutable\Map;
use function Innmind\Immutable\assertMap;

final class Row
{
    /** @var Map<string, mixed> */
    private Map $columns;

    /**
     * @param Map<string, mixed> $columns
     */
    public function __construct(Map $columns)
    {
        assertMap('string', 'mixed', $columns, 1);

        $this->columns = $columns;
    }

    /**
     * @param array<string, mixed> $columns
     */
    public static function of(array $columns): self
    {
        /** @var Map<string, mixed> */
        $map = Map::of('string', 'mixed');

        /**
         * @var mixed $value
         */
        foreach ($columns as $key => $value) {
            $map = ($map)($key, $value);
        }

        return new self($map);
    }

    public function contains(string $name): bool
    {
        return $this->columns->contains($name);
    }

    /**
     * @return mixed
     */
    public function column(string $name)
    {
        return $this->columns->get($name);
    }
}
