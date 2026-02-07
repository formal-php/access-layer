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
        return Set::compose(
            static fn(string $firstChar, string $name): Model => Model::of($firstChar.$name),
            Set::either( // table name can't start with a number
                Set::strings()->chars()->lowercaseLetter(),
                Set::strings()->chars()->uppercaseLetter(),
            ),
            Set::strings()
                ->madeOf(
                    Set::strings()->chars()->alphanumerical(),
                    Set::of('é', 'è', 'ê', 'ë', '_'),
                )
                ->between(0, 63),
        )->toSet();
    }

    /**
     * @return Set<array{0: Model, 1: Model}>
     */
    public static function pair(): Set
    {
        return Set::compose(
            static fn($a, $b) => [$a, $b],
            self::any(),
            self::any(),
        )->filter(static fn($pair) => $pair[0]->toString() !== $pair[1]->toString());
    }
}
