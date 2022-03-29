<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Securities;
use APITester\Definition\Collection\Tokens;
use APITester\Definition\Security;
use APITester\Test\TestCase;
use Illuminate\Support\Collection;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

abstract class AuthorisationErrorTestCasesPreparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function generateTestCases(Operations $operations): iterable
    {
        /** @var Securities $securities */
        $securities = $operations
            ->where('responses.*.statusCode', 'contains', $this->getStatusCode())
            ->select('securities.*')
            ->flatten()
        ;

        /** @var iterable<array-key, TestCase> */
        return $securities
            ->map(fn (Security $security) => $this->prepareTestCases($security))
            ->flatten()
        ;
    }

    abstract protected function getStatusCode(): int;

    abstract protected function getTestTokens(Security $security): Tokens;

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
            $request = $this->setAuthentication(
                new Request(
                    $operation->getMethod(),
                    $operation->getExamplePath(),
                    [],
                    $this->generateRandomBody($operation),
                ),
                $security,
                $token,
            );
            $testCases->add(
                $this->buildTestCase(
                    $operation,
                    $request,
                    new Response($this->getStatusCode()),
                    false,
                ),
            );
        }

        return $testCases;
    }
}
