<?php

declare(strict_types=1);

namespace APITester\Test\Preparator;

use APITester\Schema\Entity\Collection\Operations;
use APITester\Schema\Entity\Example\ResponseExample;
use APITester\Schema\Entity\Parameter;
use APITester\Schema\Entity\Response as DefinitionResponse;
use APITester\Test\Entity\TestCase;
use cebe\openapi\spec\Schema;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

final class Error404Preparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function prepare(Operations $operations): iterable
    {
        $testCases = [];

        foreach ($operations as $operation) {
            foreach ($operation->getResponses()->where('statusCode', 404) as $response) {
                $testCase = $this->prepareTestCase($response);
                if ($testCase !== null) {
                    $testCases[] = $testCase;
                }
            }
        }

        return $testCases;
    }

    private function prepareTestCase(DefinitionResponse $response): ?TestCase
    {
        $operation = $response->getParent();

        $parameter = $operation->getPathParameters()
            ->reverse()
            ->first(fn (Parameter $parameter) => $this->supportsNotFoundValue($parameter))
        ;
        if (!$parameter instanceof Parameter) {
            return null;
        }

        $example = $operation->getExample();
        $example->getPathParameters();
        $example = $example->withParameter(
            $parameter->getName(),
            $this->getNotFoundValue($parameter),
            Parameter::TYPE_PATH
        );
        $example->getQueryParameters();
        $example->getHeaders();
        $example->getBody();

        $example
            ->setName('RandomPath')
            ->setAutoComplete(false)
            ->setResponse(
                ResponseExample::create()
                    ->setStatusCode($this->config->response->getStatusCode() ?? '404')
                    ->setHeaders($this->config->response->headers ?? [])
                    ->setContent($this->config->response->body ?? $response->getDescription())
            )
        ;

        return $this->buildTestCase($example);
    }

    private function supportsNotFoundValue(Parameter $parameter): bool
    {
        $schema = $parameter->getSchema();

        return $schema instanceof Schema
            && empty($schema->enum)
            && \in_array($schema->type, ['integer', 'string'], true);
    }

    private function getNotFoundValue(Parameter $parameter): string
    {
        $schema = $parameter->getSchema();
        if (!$schema instanceof Schema) {
            throw new \LogicException('A schema is required to build a not-found value.');
        }

        if ($schema->type === 'integer') {
            return (string) $this->getNotFoundInteger($schema);
        }

        return $this->getNotFoundString($schema);
    }

    private function getNotFoundInteger(Schema $schema): int
    {
        $maximum = $schema->maximum ?? match ($schema->format) {
            'int32' => 2_147_483_647,
            default => PHP_INT_MAX,
        };
        $value = (int) $maximum;
        if ($schema->exclusiveMaximum) {
            --$value;
        }

        $multipleOf = (int) ($schema->multipleOf ?? 1);
        if ($multipleOf > 1) {
            $value -= $value % $multipleOf;
        }

        return $value;
    }

    private function getNotFoundString(Schema $schema): string
    {
        $candidates = [
            'api-tester-not-found',
            'apitesternotfound',
            'APITESTERNOTFOUND',
            'ffffffff-ffff-4fff-bfff-ffffffffffff',
            str_repeat('z', $schema->maxLength ?? 32),
            str_repeat('9', $schema->maxLength ?? 32),
        ];

        $schemaData = (array) $schema->getSerializableData();
        unset($schemaData['default'], $schemaData['example']);
        $schemaData['nullable'] = false;
        $staticValue = (new SchemaFaker(
            new Schema($schemaData),
            (new Options())->setStrategy(Options::STRATEGY_STATIC)
        ))->generate();
        if (\is_string($staticValue)) {
            $candidates[] = $staticValue;
        }

        $bestCandidate = null;
        foreach ($candidates as $candidate) {
            $candidate = $this->fitStringLength($candidate, $schema);
            $minimumLength = max($schema->minLength ?? 0, 1);
            for ($length = mb_strlen($candidate); $length >= $minimumLength; --$length) {
                $value = mb_substr($candidate, 0, $length);
                if ($this->matchesStringSchema($value, $schema)) {
                    if ($bestCandidate === null || mb_strlen($value) > mb_strlen($bestCandidate)) {
                        $bestCandidate = $value;
                    }
                    break;
                }
            }
        }

        if ($bestCandidate !== null) {
            return $bestCandidate;
        }

        throw new \LogicException('Could not build a schema-valid not-found value.');
    }

    private function fitStringLength(string $value, Schema $schema): string
    {
        $minimum = $schema->minLength ?? 0;
        $maximum = $schema->maxLength;

        if (mb_strlen($value) < $minimum) {
            $value .= str_repeat('z', $minimum - mb_strlen($value));
        }
        if ($maximum !== null && mb_strlen($value) > $maximum) {
            $value = mb_substr($value, 0, $maximum);
        }

        return $value;
    }

    private function matchesStringSchema(string $value, Schema $schema): bool
    {
        $length = mb_strlen($value);
        if ($length < ($schema->minLength ?? 0)
            || ($schema->maxLength !== null && $length > $schema->maxLength)) {
            return false;
        }

        if ($schema->format === 'uuid'
            && preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                $value
            ) !== 1) {
            return false;
        }

        if ($schema->pattern === null) {
            return true;
        }

        $pattern = str_replace('~', '\\~', $schema->pattern);

        return preg_match("~{$pattern}~u", $value) === 1;
    }
}
