<?php
declare(strict_types = 1);

namespace Fixtures\Formal\AccessLayer\Table\Column;

use Formal\AccessLayer\Table\Column\Type as Model;
use Innmind\BlackBox\Set;

final class Type
{
    /**
     * @return Set<Model>
     */
    public static function any(): Set
    {
        return Set::either(
            self::bigint(),
            self::binary(),
            self::bit(),
            self::char(),
            self::decimal(),
            self::int(),
            self::mediumint(),
            self::smallint(),
            self::tinyint(),
            self::varchar(),
            self::of(),
            self::nullable(),
            self::comment(),
            self::nullable(self::comment()),
            Set::of(Model::uuid(), Model::bool()),
        );
    }

    /**
     * @return Set<Model>
     */
    public static function constraint(): Set
    {
        return Set::either(
            self::bigint(),
            self::binary(),
            self::bit(),
            self::char(),
            self::decimal(),
            self::int(),
            self::mediumint(),
            self::smallint(),
            self::tinyint(),
            self::varchar(),
            Set::of(Model::uuid(), Model::bool()),
        );
    }

    /**
     * @return Set<Model>
     */
    private static function bigint(): Set
    {
        return Set::integers()
            ->between(1, 255)
            ->map(Model::bigint(...));
    }

    /**
     * @return Set<Model>
     */
    private static function binary(): Set
    {
        return Set::integers()
            ->between(1, 255)
            ->map(Model::binary(...));
    }

    /**
     * @return Set<Model>
     */
    private static function bit(): Set
    {
        return Set::integers()
            ->between(1, 64)
            ->map(Model::bit(...));
    }

    /**
     * @return Set<Model>
     */
    private static function char(): Set
    {
        return Set::integers()
            ->between(1, 255)
            ->map(Model::char(...));
    }

    /**
     * @return Set<Model>
     */
    private static function decimal(): Set
    {
        return Set::either(
            Set::integers()
                ->between(1, 65)
                ->map(Model::decimal(...)),
            Set::compose(
                static fn($precision, $scale) => [$precision, $scale],
                Set::integers()->between(1, 65),
                Set::integers()->between(0, 30),
            )
                ->filter(static fn($precision) => $precision[1] <= $precision[0]) // scale can't be higher than the precision
                ->map(static fn($precision) => Model::decimal(...$precision)),
        );
    }

    /**
     * @return Set<Model>
     */
    private static function int(): Set
    {
        return Set::integers()
            ->between(1, 255)
            ->map(Model::int(...));
    }

    /**
     * @return Set<Model>
     */
    private static function mediumint(): Set
    {
        return Set::integers()
            ->between(1, 255)
            ->map(Model::mediumint(...));
    }

    /**
     * @return Set<Model>
     */
    private static function smallint(): Set
    {
        return Set::integers()
            ->between(1, 255)
            ->map(Model::smallint(...));
    }

    /**
     * @return Set<Model>
     */
    private static function tinyint(): Set
    {
        return Set::integers()
            ->between(1, 255)
            ->map(Model::tinyint(...));
    }

    /**
     * @return Set<Model>
     */
    private static function varchar(): Set
    {
        return Set::integers()
            ->between(1, 255)
            ->map(Model::varchar(...));
    }

    /**
     * @return Set<Model>
     */
    private static function of(): Set
    {
        return Set::of(
            'bigint',
            'binary',
            'bit',
            'blob',
            'char',
            'date',
            'datetime',
            'decimal',
            'double',
            'float',
            'int',
            'json',
            'longtext',
            'mediumint',
            'mediumtext',
            'smallint',
            'text',
            'tinyint',
            'varchar',
        )->map(static fn(string $name): Model => Model::$name());
    }

    /**
     * @return Set<Model>
     */
    private static function nullable(?Set $set = null): Set
    {
        return ($set ?? self::of())->map(
            static fn(Model $type): Model => $type->nullable(),
        );
    }

    /**
     * @return Set<Model>
     */
    private static function comment(): Set
    {
        return Set::compose(
            static fn(Model $type, string $comment): Model => $type->comment($comment),
            self::of(),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->atLeast(1),
        )->toSet();
    }
}
