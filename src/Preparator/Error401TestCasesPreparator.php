<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Firebase\JWT\JWT;
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

final class Error401TestCasesPreparator extends TestCasesPreparator
{
    public const FAKE_API_KEY = 'b85a985d-0114-4a23-8419-49f64a4c12f8';

    private string $fakeApiKey = self::FAKE_API_KEY;

    public static function getName(): string
    {
        return '401';
    }

    /**
     * @inheritDoc
     */
    public function prepare(Api $api): iterable
    {
        /** @var Securities $securities */
        $securities = $api->getOperations()
            ->where('responses.*.statusCode', [401])
            ->select('securities.*')
            ->flatten();

        return $securities
            ->map(fn (Security $security) => $this->prepareTestCase($security))
        ;
    }

    private function prepareTestCase(Security $security): TestCase
    {
        $operation = $security->getOperation();
        $request = $this->setAuthentication(
            new Request(
                $operation->getMethod(),
                $operation->getPath()
            ),
            $security
        );

        return new TestCase(
            $operation->getId(),
            $request,
            new Response(401),
            $this->getGroups($operation),
        );
    }

    private function setAuthentication(Request $request, Security $security): Request
    {
        if ($security instanceof HttpSecurity && $security->isBasic()) {
            $request = $this->addFakeBasicHeader($request);
        } elseif ($security instanceof HttpSecurity && $security->isBearer()) {
            $request = $this->addFakeBearerToken($request);
        } elseif ($security instanceof OAuth2Security) {
            $request = $this->addFakeOAuth2Token($request);
        } elseif ($security instanceof ApiKeySecurity) {
            $request = $this->addFakeApiKeyToRequest($request, $security);
        }

        return $request;
    }

    private function addFakeBasicHeader(Request $request): Request
    {
        return $request->withAddedHeader('Authorization', 'Basic ' . base64_encode('aaaa:bbbbb'));
    }

    private function addFakeBearerToken(Request $request): Request
    {
        return $request->withAddedHeader(
            'Authorization',
            'Bearer ' . JWT::encode([
                'test' => 1234,
            ], 'abcd')
        );
    }

    private function addFakeOAuth2Token(Request $request): Request
    {
        return $this->addFakeBearerToken($request);
    }

    private function addFakeApiKeyToRequest(Request $request, ApiKeySecurity $security): Request
    {
        $newRequest = $request;
        if ('header' === $security->getIn()) {
            $newRequest = $request->withAddedHeader($security->getKeyName(), $this->fakeApiKey);
        } elseif ('cookie' === $security->getIn()) {
            $newRequest = $request->withAddedHeader('Cookie', "{$security->getKeyName()}={$this->fakeApiKey}");
        } elseif ('query' === $security->getIn()) {
            $oldUri = (string) $request->getUri();
            $prefix = str_contains($oldUri, '?') ? '&' : '?';
            $newRequest = $request->withUri(
                new Uri($oldUri . "{$prefix}{$security->getKeyName()}={$this->fakeApiKey}")
            );
        }

        return $newRequest;
    }
}
