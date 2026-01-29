<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Tokens;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Response;
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
        return $operations
            ->filter(
                fn (Operation $o) => $o->getResponses()
                    ->filter(
                        fn (Response $r) => $r->getStatusCode() === (int) $this->getStatusCode()
                    )
            )
            ->map(
                fn (Operation $operation) => $operation->getSecurities()
                    ->map(fn (Security $security) => $this->prepareTestCases($operation, $security))
                    ->flatten()
            )
            ->flatten();
    }

    abstract protected function getStatusCode(): string;

    abstract protected function getTestTokens(Security $security): Tokens;

    abstract protected function getTestCaseName(): string;

    /**
     * @return Collection<array-key, TestCase>
     */
    private function prepareTestCases(Operation $operation, Security $security): iterable
    {
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
