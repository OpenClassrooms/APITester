<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Illuminate\Support\Collection;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\Securities;
use OpenAPITesting\Definition\Collection\Tokens;
use OpenAPITesting\Definition\Security;
use OpenAPITesting\Test\TestCase;

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
                new TestCase(
                    $operation->getId() . '_' . $this->getStatusCode() . '_' . $token->getAuthType(),
                    $request,
                    new Response($this->getStatusCode()),
                ),
            );
        }

        return $testCases;
    }
}
