<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Collection\Securities;
use OpenAPITesting\Definition\Security;
use OpenAPITesting\Definition\Security\ApiKeySecurity;
use OpenAPITesting\Definition\Security\HttpSecurity;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2Security;
use OpenAPITesting\Test\TestCase;

abstract class AuthorisationErrorTestCasesPreparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    public function prepare(Api $api): iterable
    {
        /** @var Securities $securities */
        $securities = $api->getOperations()
            ->where('responses.*.statusCode', [$this->getStatusCode()])
            ->select('securities.*')
            ->flatten();

        return $securities
            ->map(fn (Security $security) => $this->prepareTestCases($security))
            ->flatten()
        ;
    }

    abstract protected function getStatusCode(): int;

    /**
     * @return TestCase[]
     */
    private function prepareTestCases(Security $security): iterable
    {
        $operation = $security->getParent();
        $tokens = $this->getTestTokens($security);
        $testCases = collect();
        foreach ($tokens as $token) {
            $request = $this->setAuthentication(
                new Request(
                    $operation->getMethod(),
                    $operation->getPath()
                ),
                $security,
                (string) $token
            );
            $testCases->add(
                new TestCase(
                    $operation->getId(),
                    $request,
                    new Response($this->getStatusCode()),
                    $this->getGroups($operation),
                )
            );
        }

        return $testCases;
    }

    /**
     * @return array<string|int>
     */
    abstract protected function getTestTokens(Security $security): array;

    private function setAuthentication(Request $request, Security $security, string $token): Request
    {
        if ($security instanceof HttpSecurity && $security->isBasic()) {
            $request = $request->withAddedHeader(
                'Authorization',
                "Basic {$token}"
            );
        } elseif ($security instanceof HttpSecurity && $security->isBearer()) {
            $request = $request->withAddedHeader(
                'Authorization',
                "Bearer {$token}"
            );
        } elseif ($security instanceof OAuth2Security) {
            $request = $request->withAddedHeader(
                'Authorization',
                "Bearer {$token}"
            );
        } elseif ($security instanceof ApiKeySecurity) {
            $request = $this->addApiKeyToRequest(
                $request,
                $security,
                $token
            );
        }

        return $request;
    }

    private function addApiKeyToRequest(Request $request, ApiKeySecurity $security, string $apiKey): Request
    {
        $newRequest = $request;
        if ('header' === $security->getIn()) {
            $newRequest = $request->withAddedHeader($security->getKeyName(), $apiKey);
        } elseif ('cookie' === $security->getIn()) {
            $newRequest = $request->withAddedHeader('Cookie', "{$security->getKeyName()}={$apiKey}");
        } elseif ('query' === $security->getIn()) {
            $oldUri = (string) $request->getUri();
            $prefix = str_contains($oldUri, '?') ? '&' : '?';
            $newRequest = $request->withUri(
                new Uri($oldUri . "{$prefix}{$security->getKeyName()}={$apiKey}")
            );
        }

        return $newRequest;
    }
}
