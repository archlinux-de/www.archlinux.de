<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\Symfony\Bridge\Symfony\Routing\SymfonyRoutesProvider;
use Rector\Symfony\Contract\Bridge\Symfony\Routing\SymfonyRoutesProviderInterface;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/migrations',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()
    ->withPhpSets()
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withAttributesSets()
    ->withSymfonyContainerPhp(__DIR__ . '/tests/symfony-container.php')
    ->registerService(SymfonyRoutesProvider::class, SymfonyRoutesProviderInterface::class)
    ->withPreparedSets(deadCode: true, codeQuality: true, typeDeclarations: true)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        NewMethodCallWithoutParenthesesRector::class,
        ReadOnlyPropertyRector::class,
        RenameClassRector::class,
        ExplicitBoolCompareRector::class,
        DisallowedEmptyRuleFixerRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        // want to replace [0-9]  with \d which is not the same though
        SimplifyRegexPatternRector::class,
    ]);
