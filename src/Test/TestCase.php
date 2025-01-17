<?php

declare(strict_types=1);

namespace APITester\Test;

use APITester\Definition\Body;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Requester\Requester;
use APITester\Requester\SymfonyKernelRequester;
use APITester\Test\Exception\InvalidResponseSchemaException;
use APITester\Util\Assert;
use APITester\Util\Filterable;
use APITester\Util\Json;
use APITester\Util\Random;
use APITester\Util\Serializer;
use APITester\Util\Traits\FilterableTrait;
use APITester\Util\Traits\TimeBoundTrait;
use Carbon\Carbon;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Stream;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @internal
 * @coversNothing
 */
final class TestCase implements \JsonSerializable, Filterable
{
    use TimeBoundTrait;
    use FilterableTrait;

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
        'parent',
    ];

    private bool $schemaValidation;

    private readonly string $id;

    private LoggerInterface $logger;

    private RequestInterface $request;

    private Requester $requester;

    private ResponseInterface $response;

    private ?string $operation;

    private string $preparator;

    private Validator $validator;

    private ErrorFormatter $errorFormatter;

    /**
     * @param array<int, string> $excludedFields
     */
    public function __construct(
        private readonly string $name,
        private readonly OperationExample $operationExample,
        array $excludedFields = [],
        bool $schemaValidation = true
    ) {
        $this->logger = new NullLogger();
        $this->id = Random::id('testcase_');
        $this->excludedFields = array_unique([...$this->excludedFields, ...$excludedFields]);
        $nameParts = explode(' - ', $name);
        $this->preparator = $nameParts[0] ?? '';
        $this->operation = $nameParts[1] ?? null;
        $this->request = $operationExample->getPsrRequest();
        $this->schemaValidation = $schemaValidation;
        $this->validator = new Validator();
        $this->errorFormatter = new ErrorFormatter();
    }

    /**
     * @param string[] $excludedFields
     */
    public function addExcludedFields(array $excludedFields): void
    {
        /** @var string[] excludedFields */
        $this->excludedFields = array_merge($excludedFields, $this->excludedFields);
    }

    public function setSchemaValidation(bool $schemaValidation): void
    {
        $this->schemaValidation = $schemaValidation;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws ExceptionInterface
     * @throws InvalidResponseSchemaException
     */
    public function test(?HttpKernelInterface $kernel = null): void
    {
        if ($kernel !== null && $this->requester instanceof SymfonyKernelRequester) {
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

    /**
     * @throws ExceptionInterface
     * @throws InvalidResponseSchemaException
     */
    public function assert(): void
    {
        $this->response = $this->response ?? $this->requester->getResponse($this->id);

        $this->checkSchemaResponse();

        try {
            Assert::response(
                $this->operationExample->getResponse(),
                ResponseExample::fromPsrResponse($this->response),
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
        return $this->name;
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

    public function withRequestBody(Body $request): self
    {
        $request = $this->request->withBody(Stream::create($request->getStringExample()));

        return $this->withRequest($request);
    }

    public function withRequest(RequestInterface $request): self
    {
        $self = clone $this;
        $self->request = $request;

        return $self;
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public function setOperation(string $operation): void
    {
        $this->operation = $operation;
    }

    public function getPreparator(): string
    {
        return $this->preparator;
    }

    public function setPreparator(string $preparator): void
    {
        $this->preparator = $preparator;
    }

    public function getHash(): string
    {
        return hash('sha3-256', Json::encode($this->jsonSerialize()));
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    /**
     * @return array{'name': string, 'request': RequestInterface, 'response': ResponseExample}
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'request' => $this->request,
            'response' => $this->operationExample->getResponse(),
        ];
    }

    /**
     * @throws ExceptionInterface
     */
    private function log(string $logLevel): void
    {
        $message = Json::encode([
            'name' => $this->getName(),
            'startedAt' => $this->getStartedAt(),
            'finishedAt' => $this->getFinishedAt(),
            'request' => Serializer::normalize($this->request),
            'response' => Serializer::normalize($this->response),
            'expected' => Serializer::normalize($this->operationExample->getResponse(), $this->excludedFields),
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
                    public function getMetadata(): array
                    {
                        return \$this->testCase->getMetadata();
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

    private function getSchemaResponseForStatusCode(int $statusCode): ?Schema
    {
        if ($this->operationExample->getParent() === null) {
            return null;
        }

        foreach ($this->operationExample->getParent()->getResponses() as $schemaResponse) {
            if ($schemaResponse->getStatusCode() === $statusCode) {
                return $schemaResponse->getBody();
            }
        }

        return null;
    }

    /**
     * @throws InvalidResponseSchemaException
     */
    private function checkSchemaResponse(): void
    {
        if (!$this->schemaValidation) {
            return;
        }

        $schema = $this->getSchemaResponseForStatusCode($this->response->getStatusCode());

        if ($schema === null) {
            return;
        }

        $data = json_decode((string) ResponseExample::fromPsrResponse($this->response)->getContent());
        $schemaData = (object) $schema->getSerializableData();

        $result = $this->validator->validate($data, $schemaData);

        if (!$result->isValid()) {
            if ($result->error() !== null) {
                $errorDescription = (string) json_encode($this->errorFormatter->format($result->error()));
                $this->logger->error($errorDescription);
            }
            throw new InvalidResponseSchemaException();
        }
    }
}
