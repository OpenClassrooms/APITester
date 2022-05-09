<?php

declare(strict_types=1);

namespace APITester\Definition\Loader;

use APITester\Definition\Collection\OperationExamples;
use APITester\Definition\Collection\Operations;
use APITester\Util\Serializer;
use APITester\Util\Yaml;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final class ExamplesExtensionLoader
{
    /**
     * @throws ExceptionInterface
     */
    public static function load(string $path, Operations $operations): Operations
    {
        $data = self::loadYaml($path);
        $examples = Serializer::denormalize($data, OperationExamples::class);

        return self::addExamplesToOperations(
            $data['operations'],
            $operations,
            $examples
        );
    }

    /**
     * @return array{examples: array<mixed>, operations: array<string, array<string>>}
     */
    private static function loadYaml(string $path): array
    {
        if (is_dir($path)) {
            $data = Yaml::concatFromDirectory($path);
        } else {
            $data = Yaml::parseFile($path);
        }

        /** @var array{examples: array<mixed>, operations: array<string, array<string>>} */
        return $data;
    }

    /**
     * @param array<string, array<string>> $links
     */
    private static function addExamplesToOperations(
        array $links,
        Operations $operations,
        OperationExamples $examples
    ): Operations {
        foreach ($operations as $operation) {
            $operationExamples = $links[$operation->getId()] ?? [];
            foreach ($operationExamples as $exampleName) {
                $example = $examples
                    ->where('name', $exampleName)
                    ->first()
                ;
                if (null !== $example) {
                    $operation->addExample($example);
                }
            }
        }

        return $operations;
    }
}
