<?php

declare(strict_types=1);

namespace APITester\Preparator\Foundation;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Tokens;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Security;
use APITester\Preparator\TestCasesPreparator;
use APITester\Test\TestCase;
use Illuminate\Support\Collection;

abstract class SecurityErrorPreparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function prepare(Operations $operations): iterable
    {
        /** @var TestCase[] */
        return $operations
            ->where('responses.*.statusCode', 'contains', $this->getStatusCode())
            ->select('securities.*')
            ->flatten()
            ->map(function ($security) {
                /** @var Security $security */
                return $this->prepareTestCases($security);
            })
            ->flatten()
        ;
    }

    abstract protected function getStatusCode(): int;

    abstract protected function getTestTokens(Security $security): Tokens;

    abstract protected function getTestCaseName(): string;

    /**
     * @return Collection<array-key, TestCase>
     */
    private function prepareTestCases(Security $security): iterable
    {
        $operation = $security->getParent();
        $tokens = $this->getTestTokens($security);
        /** @var Collection<array-key, TestCase> $testCases */
        $testCases = collect();
        foreach ($tokens as $token) {
            if ($token->getAuthType() !== $security->getType()) {
                continue;
            }
            if (0 === $operation->getRequestBodies()->count()) {
                $testCases->add(
                    $this->buildTestCase(
                        OperationExample::create($this->getTestCaseName(), $operation)
                            ->setHeaders($this->getAuthenticationParams($security, $token)['headers'] ?? [])
                            ->setQueryParameters($this->getAuthenticationParams($security, $token)['query'] ?? [])
                            ->setResponse(ResponseExample::create()->setStatusCode($this->getStatusCode())),
                        false,
                    ),
                );
            }
            foreach ($operation->getRequestBodies() as $ignored) {
                $testCases->add(
                    $this->buildTestCase(
                        OperationExample::create($this->getTestCaseName(), $operation)
                            ->setHeaders($this->getAuthenticationParams($security, $token)['headers'] ?? [])
                            ->setQueryParameters($this->getAuthenticationParams($security, $token)['query'] ?? [])
                            ->setResponse(ResponseExample::create()->setStatusCode($this->getStatusCode())),
                        false,
                    ),
                );
            }
        }

        return $testCases;
    }
}
