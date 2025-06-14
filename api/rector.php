<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\If_\RemoveUnusedNonEmptyArrayBeforeForeachRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Php55\Rector\Class_\ClassConstantToSelfClassRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php83\Rector\Class_\ReadOnlyAnonymousClassRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\Symfony\Bridge\Symfony\Routing\SymfonyRoutesProvider;
use Rector\Symfony\Contract\Bridge\Symfony\Routing\SymfonyRoutesProviderInterface;
use Rector\Symfony\Symfony73\Rector\Class_\GetFiltersToAsTwigFilterAttributeRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\Class_\TypedPropertyFromCreateMockAssignRector;
use Rector\TypeDeclaration\Rector\Closure\ClosureReturnTypeRector;

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
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        NewMethodCallWithoutParenthesesRector::class,
        ReadOnlyPropertyRector::class,
        RenameClassRector::class,
        ReadOnlyAnonymousClassRector::class,
        ClassConstantToSelfClassRector::class,
        ChangeSwitchToMatchRector::class,
        RemoveUnusedVariableInCatchRector::class,
        AddOverrideAttributeToOverriddenMethodsRector::class,
        AddOverrideAttributeToOverriddenMethodsRector::class,
        GetFiltersToAsTwigFilterAttributeRector::class,
        FirstClassCallableRector::class,
        NullToStrictStringFuncCallArgRector::class,
        ClosureToArrowFunctionRector::class,
        ExplicitBoolCompareRector::class,
        DisallowedEmptyRuleFixerRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        UseIdenticalOverEqualWithSameTypeRector::class,
        ClosureReturnTypeRector::class,
        AddArrowFunctionReturnTypeRector::class,
        TypedPropertyFromCreateMockAssignRector::class,
        SimplifyRegexPatternRector::class,
        SimplifyIfElseToTernaryRector::class,
        SwitchNegatedTernaryRector::class,
        RemoveUnusedNonEmptyArrayBeforeForeachRector::class,
        RemoveUselessVarTagRector::class,
        RemoveUselessReturnTagRector::class,
    ]);
