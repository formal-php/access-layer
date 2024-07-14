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
            static fn(string $firstChar, string $name): Model => Model::of($firstChar.$name),
            Set\Either::any( // table name can't start with a number
                Set\Chars::lowercaseLetter(),
                Set\Chars::uppercaseLetter(),
            ),
            Set\Strings::madeOf(
                Set\Chars::alphanumerical(),
                Set\Elements::of('é', 'è', 'ê', 'ë', '_'),
            )->between(0, 63),
        );
    }

    /**
     * @return Set<array{0: Model, 1: Model}>
     */
    public static function pair(): Set
    {
        return Set\Composite::immutable(
            static fn($a, $b) => [$a, $b],
            self::any(),
            self::any(),
        )->filter(static fn($pair) => $pair[0]->toString() !== $pair[1]->toString());
    }
}
