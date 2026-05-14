<?php
declare(strict_types = 1);

require 'vendor/autoload.php';

use Innmind\BlackBox\{
    Application,
    Runner\Load,
    Runner\CodeCoverage,
};

Application::new($argv)
    ->disableMemoryLimit()
    ->scenariiPerProof(10)
    ->when(
        \getenv('ENABLE_COVERAGE') !== false,
        static fn($app) => $app
            ->codeCoverage(
                CodeCoverage::of(
                    __DIR__.'/src/',
                    __DIR__.'/proofs/',
                    __DIR__.'/fixtures/',
                )
                    ->dumpTo('coverage.clover'),
            )
            ->scenariiPerProof(1),
    )
    ->when(
        \getenv('BLACKBOX_SET_SIZE') !== false,
        static fn(Application $app) => $app->scenariiPerProof((int) \getenv('BLACKBOX_SET_SIZE')),
    )
    ->tryToProve(Load::everythingIn(__DIR__.'/proofs/'))
    ->exit();
