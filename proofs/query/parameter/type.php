<?php
declare(strict_types = 1);

use Formal\AccessLayer\Query\Parameter\Type;
use Innmind\BlackBox\Set;

return static function($prove) {
    yield $prove->test(
        'Type::for() bool',
        static function($assert) {
            $assert->same(
                Type::bool,
                Type::for(true),
            );
            $assert->same(
                Type::bool,
                Type::for(false),
            );
        },
    );

    yield $prove->test(
        'Type::for() null',
        static function($assert) {
            $assert->same(
                Type::null,
                Type::for(null),
            );
        },
    );

    yield $prove
        ->proof('Type::for() int')
        ->given(Set::integers())
        ->test(static function($assert, $int) {
            $assert->same(
                Type::int,
                Type::for($int),
            );
        });

    yield $prove
        ->proof('Type::for() string')
        ->given(Set::strings()->unicode())
        ->test(static function($assert, $string) {
            $assert->same(
                Type::string,
                Type::for($string),
            );
        });

    yield $prove
        ->proof('Type::for() unsupported data')
        ->given(
            Set::of(
                new \stdClass,
                new class {},
                static fn() => null,
                \tmpfile(),
            ),
        )
        ->test(static function($assert, $value) {
            $assert->same(
                Type::unspecified,
                Type::for($value),
            );
        });
};
