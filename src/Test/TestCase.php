<?php

declare(strict_types=1);

namespace APITester\Test;

use APITester\Definition\Body;
use APITester\Requester\Requester;
use APITester\Requester\SymfonyKernelRequester;
use APITester\Util\Assert;
use APITester\Util\Json;
use APITester\Util\Serializer;
use APITester\Util\Traits\TimeBoundTrait;
use Carbon\Carbon;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 * @coversNothing
 */
final class TestCase implements \JsonSerializable
{
    use TimeBoundTrait;

    /**
     * @var \Closure[]
     */
    private array $afterCallbacks = [];

    /**
     * @var \Closure[]
     */
    private array $beforeCallbacks = [];

    /**
     * @var array<int, string>
     */
    private array $excludedFields = [
        'headers',
        'reasonPhrase',
        'headerNames',
        'protocol',
    ];

    private ResponseInterface $expectedResponse;

    private string $id;

    private LoggerInterface $logger;

    private RequestInterface $request;

    private Requester $requester;

    private string $name;

    private ResponseInterface $response;

    /**
     * @param array<int, string> $excludedFields
     */
    public function __construct(
        string $name,
        RequestInterface $request,
        ResponseInterface $expectedResponse,
        array $excludedFields = []
    ) {
        $this->request = $request;
        $this->expectedResponse = $expectedResponse;
        $this->logger = new NullLogger();
        $this->id = uniqid('testcase_', false);
        $this->excludedFields = array_unique([...$this->excludedFields, ...$excludedFields]);
        $this->name = $name;
    }

    /**
     * @param string[] $excludedFields
     */
    public function addExcludedFields(array $excludedFields): void
    {
        /** @var string[] excludedFields */
        $this->excludedFields = array_merge($excludedFields, $this->excludedFields);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function test(?HttpKernelInterface $kernel = null): void
    {
        if (null !== $kernel && $this->requester instanceof SymfonyKernelRequester) {
            $this->requester->setKernel($kernel);
        }
        $this->prepare();
        $this->assert();
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function prepare(): void
    {
        foreach ($this->beforeCallbacks as $callback) {
            ($callback)();
        }
        $this->startedAt = Carbon::now();
        $this->requester->request($this->request, $this->id);
        $this->finishedAt = Carbon::now();
        foreach ($this->afterCallbacks as $callback) {
            ($callback)();
        }
    }

    public function assert(): void
    {
        $this->response = $this->requester->getResponse($this->id);
        try {
            Assert::response(
                $this->expectedResponse,
                $this->response,
                $this->excludedFields
            );
        } catch (ExpectationFailedException $e) {
            $this->log(LogLevel::NOTICE);
            throw $e;
        }
        $this->log(LogLevel::DEBUG);
    }

    public function getName(): string
    {
        return $this->name
            . '(' . $this->request->getMethod()
            . '_'
            . $this->request->getUri()
            . ')'
            . ' -> ' . $this->expectedResponse->getStatusCode();
    }

    /**
     * @param \Closure[] $callbacks
     */
    public function setAfterCallbacks(array $callbacks): void
    {
        $this->afterCallbacks = $callbacks;
    }

    /**
     * @param \Closure[] $callbacks
     */
    public function setBeforeCallbacks(array $callbacks): void
    {
        $this->beforeCallbacks = $callbacks;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setRequester(Requester $requester): void
    {
        $this->requester = $requester;
    }

    /**
     * @template T of \PHPUnit\Framework\TestCase
     *
     * @param class-string<T> $testCaseClass
     *
     * @return T
     */
    public function toPhpUnitTestCase(string $testCaseClass): \PHPUnit\Framework\TestCase
    {
        $className = '\ApiTestCase';
        $testCaseName = $this->getName();
        $this->declareTestCaseClass($className, $testCaseClass);

        return new $className($this, $testCaseName);
    }

    public function withAddedRequestBody(Body $request): self
    {
        return new self(
            $this->name,
            $this->getRequest()
                ->withBody(Stream::create($request->getStringExample())),
            $this->getExpectedResponse(),
            $this->excludedFields,
        );
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function setRequest(RequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getExpectedResponse(): ResponseInterface
    {
        return $this->expectedResponse;
    }

    public function withRequest(RequestInterface $request): self
    {
        $self = clone $this;
        $self->request = $request;

        return $self;
    }

    /**
     * @return array{'name': string, 'request': RequestInterface, 'response': ResponseInterface}
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'request' => $this->request,
            'response' => $this->expectedResponse,
        ];
    }

    private function log(string $logLevel): void
    {
        $message = Json::encode([
            'name' => $this->getName(),
            'startedAt' => $this->getStartedAt(),
            'finishedAt' => $this->getFinishedAt(),
            'request' => Serializer::normalize($this->request),
            'response' => Serializer::normalize($this->response),
            'expected' => Serializer::normalize($this->expectedResponse),
        ], JSON_PRETTY_PRINT);
        $this->logger->log($logLevel, $message);
    }

    private function declareTestCaseClass(string $name, string $parent): void
    {
        if (!class_exists($name)) {
            $name = str_replace('\\', '', $name);
            $code = <<<CODE_SAMPLE
                class {$name} extends {$parent} {
                    private \\APITester\\Test\\TestCase \$testCase;
                    private string \$name;
                    public function __construct(\$testCase, \$name) {
                        parent::__construct('test');
                        \$this->name = \$name;
                        \$this->testCase = \$testCase;
                    }
                    public function getName(bool \$withDataSet = true): string
                    {
                        return \$this->name;
                    }
                    public function test(): void
                    {
                        \$kernel = null;
                        if (method_exists(\$this, 'getKernel')) {
                            \$kernel = \$this->getKernel();
                        }
                        \$this->testCase->test(\$kernel);
                    }
                }
            CODE_SAMPLE;
            eval($code);
        }
    }
}
