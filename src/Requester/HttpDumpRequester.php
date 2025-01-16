<?php

declare(strict_types=1);

namespace APITester\Requester;

use APITester\Util\Json;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpDumpRequester extends Requester
{
    /**
     * @var ResponseInterface[]
     */
    private array $responses = [];

    public function request(RequestInterface $request, string $id): void
    {
        $httpDump = $this->requestToHttp($request, $id);

        echo "\n" . $httpDump . "\n";

        $response = new Response($request->getMethod() === 'POST' ? 201 : 200);
        $this->responses[$id] = $response;
    }

    public function getResponse(string $id): ResponseInterface
    {
        return $this->responses[$id];
    }

    public static function getName(): string
    {
        return 'http-dump';
    }

    private function requestToHttp(RequestInterface $request, string $requestName): string
    {
        $dump = "###\n";
        $dump .= $request->getMethod() . ' ' . '{{url}}' . $request->getUri() . "\n";

        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $dump .= "{$name}: {$value}\n";
            }
        }

        $body = (string) $request->getBody();
        if (!empty($body)) {
            if (Json::isJson($body)) {
                $body = Json::prettify($body);
            }
            $dump .= "\n" . $body . "\n";
        }

        return $dump;
    }
}
