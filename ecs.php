<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(SetList::SPACES);
    $containerConfigurator->import(SetList::STRICT);
    $containerConfigurator->import(SetList::CONTROL_STRUCTURES);
    $containerConfigurator->import(SetList::ARRAY);
    $containerConfigurator->import(SetList::DOCBLOCK);
    $containerConfigurator->import(SetList::PSR_12);
    $containerConfigurator->import(SetList::SYMPLIFY);
    $containerConfigurator->import(SetList::COMMON);
    $containerConfigurator->import(SetList::CLEAN_CODE);
    $containerConfigurator->import(SetList::PHP_CS_FIXER);
    $containerConfigurator->import(SetList::PHP_CS_FIXER_RISKY);
    $containerConfigurator->import(SetList::SYMFONY);
    $containerConfigurator->import(SetList::SYMFONY_RISKY);
    $containerConfigurator->import(SetList::PHPUNIT);

    $services = $containerConfigurator->services();
    $services->set(ArraySyntaxFixer::class)->call('configure', [
        [
            'syntax' => 'short',
        ],
    ]);
    $services->set(ConcatSpaceFixer::class)->call('configure', [
        [
            'spacing' => 'one',
        ],
    ]);
    $services->set(NoSuperfluousPhpdocTagsFixer::class)
        ->call('configure', [
            [
                'allow_mixed' => true,
            ],
        ]);
    $services->set(PhpCsFixer\Fixer\CastNotation\CastSpacesFixer::class)
        ->call('configure', [
            [
                'space' => 'none',
            ],
        ]);
//    $services->set(GeneralPhpdocAnnotationRemoveFixer::class) //buggy
//        ->call('configure', [
//            [
//                'annotations' => [
//                    'throw',
//                    'throws',
//                    'author',
//                    'authors',
//                    'package',
//                    'group',
//                    'required',
//                    'phpstan-ignore-line',
//                    'phpstan-ignore-next-line',
//                ],
//            ],
//        ]);

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [__DIR__ . '/src', __DIR__ . '/tests']);
    $parameters->set(Option::PARALLEL, true);
    $parameters->set(Option::SKIP, [
        GeneralPhpdocAnnotationRemoveFixer::class,
    ]);
};
