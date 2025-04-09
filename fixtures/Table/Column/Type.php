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
        return Set\Either::any(
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
            Set\Elements::of(Model::uuid(), Model::bool()),
        );
    }

    /**
     * @return Set<Model>
     */
    public static function constraint(): Set
    {
        return Set\Either::any(
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
            Set\Elements::of(Model::uuid(), Model::bool()),
        );
    }

    /**
     * @return Set<Model>
     */
    private static function bigint(): Set
    {
        return Set\Integers::between(1, 255)->map(Model::bigint(...));
    }

    /**
     * @return Set<Model>
     */
    private static function binary(): Set
    {
        return Set\Integers::between(1, 255)->map(Model::binary(...));
    }

    /**
     * @return Set<Model>
     */
    private static function bit(): Set
    {
        return Set\Integers::between(1, 64)->map(Model::bit(...));
    }

    /**
     * @return Set<Model>
     */
    private static function char(): Set
    {
        return Set\Integers::between(1, 255)->map(Model::char(...));
    }

    /**
     * @return Set<Model>
     */
    private static function decimal(): Set
    {
        return Set\Either::any(
            Set\Integers::between(1, 65)->map(Model::decimal(...)),
            Set\Composite::immutable(
                static fn($precision, $scale) => [$precision, $scale],
                Set\Integers::between(1, 65),
                Set\Integers::between(0, 30),
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
        return Set\Integers::between(1, 255)->map(Model::int(...));
    }

    /**
     * @return Set<Model>
     */
    private static function mediumint(): Set
    {
        return Set\Integers::between(1, 255)->map(Model::mediumint(...));
    }

    /**
     * @return Set<Model>
     */
    private static function smallint(): Set
    {
        return Set\Integers::between(1, 255)->map(Model::smallint(...));
    }

    /**
     * @return Set<Model>
     */
    private static function tinyint(): Set
    {
        return Set\Integers::between(1, 255)->map(Model::tinyint(...));
    }

    /**
     * @return Set<Model>
     */
    private static function varchar(): Set
    {
        return Set\Integers::between(1, 255)->map(Model::varchar(...));
    }

    /**
     * @return Set<Model>
     */
    private static function of(): Set
    {
        return Set\Decorate::immutable(
            static fn(string $name): Model => Model::$name(),
            Set\Elements::of(
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
            ),
        );
    }

    /**
     * @return Set<Model>
     */
    private static function nullable(?Set $set = null): Set
    {
        return Set\Decorate::immutable(
            static fn(Model $type): Model => $type->nullable(),
            $set ?? self::of(),
        );
    }

    /**
     * @return Set<Model>
     */
    private static function comment(): Set
    {
        return Set\Composite::immutable(
            static fn(Model $type, string $comment): Model => $type->comment($comment),
            self::of(),
            Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
        );
    }
}
