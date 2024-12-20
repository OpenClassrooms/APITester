<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Tokens;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Security;
use APITester\Test\TestCase;
use Illuminate\Support\Collection;

abstract class SecurityErrorPreparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function prepare(Operations $operations): iterable
    {
        /** @var iterable<array-key, TestCase> */
        return $operations
            ->where('responses.*.statusCode', 'contains', (int) $this->getStatusCode())
            ->select('securities.*')
            ->flatten()
            ->map(function ($security) {
                /** @var Security $security */
                return $this->prepareTestCases($security);
            })
            ->flatten()
        ;
    }

    abstract protected function getStatusCode(): string;

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
            if ($operation->getRequestBodies()->count() === 0) {
                $testCases->add(
                    $this->buildTestCase(
                        OperationExample::create($this->getTestCaseName(), $operation)
                            ->setAuthenticationHeaders(new Tokens([$token]), true)
                            ->setResponse(ResponseExample::create()->setStatusCode($this->getStatusCode())),
                        false,
                    ),
                );
            }
            foreach ($operation->getRequestBodies() as $ignored) {
                $testCases->add(
                    $this->buildTestCase(
                        OperationExample::create($this->getTestCaseName(), $operation)
                            ->setAuthenticationHeaders(new Tokens([$token]), true)
                            ->setResponse(ResponseExample::create()->setStatusCode($this->getStatusCode())),
                        false,
                    ),
                );
            }
        }

        return $testCases;
    }
}
