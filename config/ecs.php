<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Alias\MbStrFunctionsFixer;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\ClassNotation\FinalPublicMethodForAbstractClassFixer;
use PhpCsFixer\Fixer\FunctionNotation\CombineNestedDirnameFixer;
use PhpCsFixer\Fixer\FunctionNotation\NoUnreachableDefaultArgumentValueFixer;
use PhpCsFixer\Fixer\FunctionNotation\NoUselessSprintfFixer;
use PhpCsFixer\Fixer\FunctionNotation\SingleLineThrowFixer;
use PhpCsFixer\Fixer\FunctionNotation\StaticLambdaFixer;
use PhpCsFixer\Fixer\FunctionNotation\UseArrowFunctionsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\DirConstantFixer;
use PhpCsFixer\Fixer\LanguageConstruct\IsNullFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\LogicalOperatorsFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Operator\TernaryToElvisOperatorFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitInternalClassFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestClassRequiresCoversFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $config): void {
    $config->paths([__DIR__ . '/../src', __DIR__ . '/../tests']);

    $config->sets(
        [
            SetList::SPACES,
            SetList::STRICT,
            SetList::CONTROL_STRUCTURES,
            SetList::ARRAY,
            SetList::DOCBLOCK,
            SetList::COMMON,
            SetList::PHPUNIT,
            SetList::PSR_12,
            SetList::SYMPLIFY,
            SetList::CLEAN_CODE,
        ]
    );

    $config->skip([
        GeneralPhpdocAnnotationRemoveFixer::class,
        NotOperatorWithSuccessorSpaceFixer::class,
        SingleLineThrowFixer::class,
        PhpUnitTestClassRequiresCoversFixer::class,
        PhpUnitInternalClassFixer::class,
        PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer::class,
        PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer::class,
    ]);

    $config->rules([
        FinalPublicMethodForAbstractClassFixer::class,
        CombineNestedDirnameFixer::class,
        NoUselessSprintfFixer::class,
        NoUnreachableDefaultArgumentValueFixer::class,
        StaticLambdaFixer::class,
        UseArrowFunctionsFixer::class,
        DirConstantFixer::class,
        IsNullFixer::class,
        LogicalOperatorsFixer::class,
        TernaryToElvisOperatorFixer::class,
        DeclareStrictTypesFixer::class,
        StrictParamFixer::class,
        MbStrFunctionsFixer::class,
    ]);

    $config->rulesWithConfiguration([
        ArraySyntaxFixer::class => [
            'syntax' => 'short',
        ],
        ConcatSpaceFixer::class => [
            'spacing' => 'one',
        ],
        NoSuperfluousPhpdocTagsFixer::class => [
            'allow_mixed' => true,
        ],
        CastSpacesFixer::class => [
            'space' => 'single',
        ],
        LineLengthFixer::class => [
            'line_length' => 120,
            'break_long_lines' => true,
            'inline_short_lines' => false,
        ],
    ]);

    $config->parallel();
};
