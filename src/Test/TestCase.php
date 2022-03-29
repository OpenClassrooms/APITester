<?php

declare(strict_types=1);

namespace APITester\Test;

use APITester\Definition\Request;
use APITester\Requester\Requester;
use APITester\Util\Assert;
use APITester\Util\Json;
use APITester\Util\Traits\TimeBoundTrait;
use Carbon\Carbon;
use Nyholm\Psr7\Stream;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * @internal
 * @coversNothing
 */
final class TestCase
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

    /**
     * @param array<int, string> $excludedFields
     */
    public function __construct(
        RequestInterface $request,
        ResponseInterface $expectedResponse,
        array $excludedFields = []
    ) {
        $this->request = $request;
        $this->expectedResponse = $expectedResponse;
        $this->logger = new NullLogger();
        $this->id = uniqid('testcase_', false);
        $this->excludedFields = [...$this->excludedFields, ...$excludedFields];
    }

    /**
     * @param string[] $excludedFields
     */
    public function addExcludedFields(array $excludedFields): void
    {
        /** @var string[] excludedFields */
        $this->excludedFields = array_merge($excludedFields, $this->excludedFields);
    }

    public function assert(): void
    {
        $response = $this->requester->getResponse($this->id);
        $this->logger->log(LogLevel::DEBUG, 'received response: ' . $response->getBody());
        Assert::response(
            $this->expectedResponse,
            $response,
            $this->excludedFields
        );
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
        $this->logger->log(
            LogLevel::DEBUG,
            Json::encode([
                'method' => $this->request->getMethod(),
                'url' => $this->request->getUri(),
                'body' => (string) $this->request->getBody(),
                'headers' => $this->request->getHeaders(),
                'expected_status' => $this->expectedResponse->getStatusCode(),
            ], JSON_PRETTY_PRINT)
        );
        $this->requester->request($this->request, $this->id);
        $this->finishedAt = Carbon::now();
        foreach ($this->afterCallbacks as $callback) {
            ($callback)();
        }
    }

    public function getName(): string
    {
        return $this->request->getMethod()
            . '_'
            . $this->request->getUri()
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
     * @param class-string<T>   $testCaseClass
     * @param class-string|null $kernelClass
     *
     * @return T
     */
    public function toPhpUnitTestCase(string $testCaseClass, ?string $kernelClass = null): \PHPUnit\Framework\TestCase
    {
        $className = '\ApiTestCase';
        $testCaseName = $this->getName();
        $this->declareClass($className, $testCaseClass);

        return new $className($this, $testCaseName, $kernelClass);
    }

    public function withAddedRequestBody(Request $request): self
    {
        return new self(
            $this->getRequest()
                ->withBody(Stream::create(Json::encode($request->getBodyFromExamples()))),
            $this->getExpectedResponse()
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

    private function declareClass(string $name, string $parent): void
    {
        if (!class_exists($name)) {
            eval(
            <<<CODE_SAMPLE
            class {$name} extends {$parent} {
                private \\APITester\\Test\\TestCase \$testCase;
                private string \$name;
    
                public function __construct(\$testCase, \$name, \$kernelClass) {
                    parent::__construct('test');
                    \$this->name = \$name;
                    \$this->testCase = \$testCase;
                    if (property_exists(static::class, 'kernelClass')) {
                        self::\$kernelClass = \$kernelClass;
                    }
                }
    
                public function getName(bool \$withDataSet = true): string
                {
                    return \$this->name;
                }
    
                public function test(): void
                {
                    \$this->testCase->assert();
                }
            }
CODE_SAMPLE
            );
        }
    }
}
