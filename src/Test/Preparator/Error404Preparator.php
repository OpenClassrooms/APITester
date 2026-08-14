<?php

declare(strict_types=1);

namespace APITester\Test\Preparator;

use APITester\Schema\Entity\Collection\Operations;
use APITester\Schema\Entity\Example\ResponseExample;
use APITester\Schema\Entity\Operation;
use APITester\Schema\Entity\Parameter;
use APITester\Schema\Entity\Response as DefinitionResponse;
use APITester\Test\Entity\TestCase;
use cebe\openapi\spec\Schema;
use Opis\JsonSchema\Validator;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

final class Error404Preparator extends TestCasesPreparator
{
    private ?Validator $schemaValidator = null;

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
        $example = $operation->getExample();
        $pathParameters = $example->getPathParameters();
        $replacements = $this->getPathReplacements($operation, $pathParameters);
        if ($replacements === []) {
            return null;
        }

        foreach ($replacements as [$parameter, $value]) {
            $example = $example->withParameter(
                $parameter->getName(),
                $value,
                Parameter::TYPE_PATH
            );
        }
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

    /**
     * @param array<string, int|string> $currentValues
     *
     * @return list<array{Parameter, string}>
     */
    private function getPathReplacements(Operation $operation, array $currentValues): array
    {
        $replacements = [];
        foreach ($operation->getPathParameters() as $parameter) {
            $value = $this->getNotFoundValue(
                $parameter,
                $currentValues[$parameter->getName()] ?? null
            );
            if ($value !== null) {
                $replacements[] = [$parameter, $value];
            }
        }

        return $replacements;
    }

    private function getNotFoundValue(Parameter $parameter, int|string|null $currentValue): ?string
    {
        $schema = $parameter->getSchema();
        if (!$schema instanceof Schema || !empty($schema->enum)) {
            return null;
        }

        return match ($schema->type) {
            'integer' => $this->getNotFoundInteger($schema, $currentValue),
            'string' => $this->getNotFoundString($schema, $currentValue),
            default => null,
        };
    }

    private function getNotFoundInteger(Schema $schema, int|string|null $currentValue): ?string
    {
        $multipleOf = $schema->multipleOf ?? 1;
        if (!\is_numeric($multipleOf)
            || (float) (int) $multipleOf !== (float) $multipleOf
            || $multipleOf < 1
            || $multipleOf > PHP_INT_MAX) {
            return null;
        }

        $minimum = $this->getMinimumInteger($schema);
        $maximum = $this->getMaximumInteger($schema);
        if ($minimum === null || $maximum === null || $minimum > $maximum) {
            return null;
        }

        $multipleOf = (int) $multipleOf;
        $maximumCandidate = $this->alignDown($maximum, $multipleOf);
        $minimumCandidate = $this->alignUp($minimum, $multipleOf);
        $candidates = [$maximumCandidate, $minimumCandidate];
        if ($maximumCandidate !== null && $maximumCandidate >= PHP_INT_MIN + $multipleOf) {
            $candidates[] = $maximumCandidate - $multipleOf;
        }
        if ($minimumCandidate !== null && $minimumCandidate <= PHP_INT_MAX - $multipleOf) {
            $candidates[] = $minimumCandidate + $multipleOf;
        }

        foreach ([2_147_483_647, 999_999_999, 1, 0, -1] as $candidate) {
            $candidates[] = $this->alignDown($candidate, $multipleOf);
        }

        foreach ($candidates as $candidate) {
            if ($candidate === null
                || (string) $candidate === (string) $currentValue
                || !$this->matchesSchema($candidate, $schema)) {
                continue;
            }

            return (string) $candidate;
        }

        return null;
    }

    private function getMinimumInteger(Schema $schema): ?int
    {
        $formatMinimum = $schema->format === 'int32' ? -2_147_483_648 : -PHP_INT_MAX;
        if ($schema->minimum === null) {
            return $formatMinimum;
        }

        $minimum = $schema->exclusiveMinimum
            ? floor((float) $schema->minimum) + 1
            : ceil((float) $schema->minimum);
        if ($minimum > PHP_INT_MAX) {
            return null;
        }

        return (int) max($minimum, $formatMinimum);
    }

    private function getMaximumInteger(Schema $schema): ?int
    {
        $formatMaximum = $schema->format === 'int32' ? 2_147_483_647 : PHP_INT_MAX;
        if ($schema->maximum === null) {
            return $formatMaximum;
        }

        $maximum = $schema->exclusiveMaximum
            ? ceil((float) $schema->maximum) - 1
            : floor((float) $schema->maximum);
        if ($maximum < -PHP_INT_MAX) {
            return null;
        }

        return (int) min($maximum, $formatMaximum);
    }

    private function alignDown(int $value, int $multipleOf): ?int
    {
        if ($multipleOf <= 0) {
            return null;
        }

        $remainder = $value % $multipleOf;
        if ($remainder === 0) {
            return $value;
        }

        $offset = $remainder > 0 ? $remainder : $multipleOf + $remainder;

        return $value < PHP_INT_MIN + $offset ? null : $value - $offset;
    }

    private function alignUp(int $value, int $multipleOf): ?int
    {
        if ($multipleOf <= 0) {
            return null;
        }

        $remainder = $value % $multipleOf;
        if ($remainder === 0) {
            return $value;
        }

        $offset = $remainder > 0 ? $multipleOf - $remainder : -$remainder;

        return $value > PHP_INT_MAX - $offset ? null : $value + $offset;
    }

    private function getNotFoundString(Schema $schema, int|string|null $currentValue): ?string
    {
        if ($schema->maxLength === 0) {
            return null;
        }

        $candidates = [
            'api-tester-not-found',
            'apitesternotfound',
            'APITESTERNOTFOUND',
            'ffffffff-ffff-4fff-bfff-ffffffffffff',
            '00000000-0000-4000-8000-000000000000',
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
                if ($value !== (string) $currentValue && $this->matchesSchema($value, $schema)) {
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

        return null;
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

    private function matchesSchema(int|string $value, Schema $schema): bool
    {
        $schemaData = (array) $schema->getSerializableData();
        foreach (['Minimum', 'Maximum'] as $bound) {
            $exclusive = "exclusive{$bound}";
            $inclusive = mb_strtolower($bound);
            if (($schemaData[$exclusive] ?? false) === true && isset($schemaData[$inclusive])) {
                $schemaData[$exclusive] = $schemaData[$inclusive];
                unset($schemaData[$inclusive]);
            } else {
                unset($schemaData[$exclusive]);
            }
        }
        unset($schemaData['example'], $schemaData['nullable']);

        $this->schemaValidator ??= new Validator();

        return $this->schemaValidator
            ->validate($value, (object) $schemaData)
            ->isValid();
    }
}
