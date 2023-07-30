<?php
declare(strict_types = 1);

namespace Fixtures\Formal\AccessLayer\Table;

use Formal\AccessLayer\Table\Column as Model;
use Innmind\BlackBox\Set;

final class Column
{
    /**
     * @return Set<Model>
     */
    public static function any(Set $type = null, int $max = null): Set
    {
        return Set\Randomize::of( // randomize to prevent same name used twice
            Set\Composite::immutable(
                static fn($name, $type): Model => new Model($name, $type),
                Column\Name::any($max),
                $type ?? Column\Type::any(),
            ),
        );
    }

    /**
     * @return Set<Model>
     */
    public static function list(): Set
    {
        return Set\Sequence::of(self::any())
            ->between(1, 20)
            ->map(static function($columns) {
                $filtered = [];

                foreach ($columns as $column) {
                    // in mysql column names are case insensitive
                    $name = \strtolower($column->name()->toString());
                    $filtered[$name] = $column;
                }

                return \array_values($filtered);
            });
    }
}
