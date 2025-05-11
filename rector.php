<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/public',
        __DIR__.'/resources',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkipPath(__DIR__.'/bootstrap/cache')
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withSets([
        \RectorLaravel\Set\LaravelLevelSetList::UP_TO_LARAVEL_110,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_CODE_QUALITY,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_COLLECTION,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_IF_HELPERS,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_STATIC_TO_INJECTION,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        earlyReturn: true,
        strictBooleans: true
    );
