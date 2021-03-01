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
    public static function any(): Set
    {
        return new Set\Randomize( // randomize to prevent same name used twice
            Set\Composite::immutable(
                static fn($name, $type): Model => new Model($name, $type),
                Column\Name::any(),
                Column\Type::any(),
            ),
        );
    }
}
