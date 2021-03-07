<?php
declare(strict_types = 1);

namespace Fixtures\Formal\AccessLayer\Table\Column;

use Formal\AccessLayer\Table\Column\Name as Model;
use Innmind\BlackBox\Set;

final class Name
{
    /**
     * @return Set<Model>
     */
    public static function any(int $max = null): Set
    {
        return Set\Decorate::immutable(
            static fn(string $name): Model => new Model($name),
            Set\Strings::madeOf(
                Set\Chars::alphanumerical(),
                Set\Elements::of('é', 'è', 'ê', 'ë', '_'),
            )->between(1, $max ?? 64),
        );
    }
}
