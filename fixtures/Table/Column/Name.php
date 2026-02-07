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
    public static function any(?int $max = null): Set
    {
        $max ??= 64;

        return Set::strings()
            ->madeOf(
                Set::strings()->chars()->alphanumerical(),
                Set::of('é', 'è', 'ê', 'ë', '_'),
            )
            ->between(1, $max)
            ->filter(static fn($string) => \mb_strlen($string, 'ascii') < $max)
            ->map(Model::of(...));
    }
}
