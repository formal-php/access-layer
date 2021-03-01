<?php
declare(strict_types = 1);

namespace Fixtures\Formal\AccessLayer\Table;

use Formal\AccessLayer\Table\Name as Model;
use Innmind\BlackBox\Set;

final class Name
{
    /**
     * @return Set<Model>
     */
    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(string $firstChar, string $name): Model => new Model($firstChar.$name),
            new Set\Either( // table name can't start with a number
                Set\Chars::lowercaseLetter(),
                Set\Chars::uppercaseLetter(),
            ),
            Set\Strings::madeOf(Set\Chars::alphanumerical())->between(0, 63),
        );
    }
}
