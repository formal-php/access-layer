<?php
declare(strict_types = 1);

use Formal\AccessLayer\Query\Parameter\Type;
use Innmind\BlackBox\Set;

return static function() {
    yield test(
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

    yield test(
        'Type::for() null',
        static function($assert) {
            $assert->same(
                Type::null,
                Type::for(null),
            );
        },
    );

    yield proof(
        'Type::for() int',
        given(Set::integers()),
        static function($assert, $int) {
            $assert->same(
                Type::int,
                Type::for($int),
            );
        },
    );

    yield proof(
        'Type::for() string',
        given(Set::strings()->unicode()),
        static function($assert, $string) {
            $assert->same(
                Type::string,
                Type::for($string),
            );
        },
    );

    yield proof(
        'Type::for() unsupported data',
        given(
            Set::of(
                new \stdClass,
                new class {},
                static fn() => null,
                \tmpfile(),
            ),
        ),
        static function($assert, $value) {
            $assert->same(
                Type::unspecified,
                Type::for($value),
            );
        },
    );
};
