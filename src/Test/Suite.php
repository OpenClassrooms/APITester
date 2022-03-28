<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use OpenAPITesting\Config\Filters;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Preparator\Exception\PreparatorLoadingException;
use OpenAPITesting\Preparator\TestCasesPreparator;
use OpenAPITesting\Requester\Requester;
use OpenAPITesting\Util\Traits\TimeBoundTrait;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\TestSuite;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 * @coversNothing
 */
final class Suite extends TestSuite
{
    use TimeBoundTrait;

    private Api $api;

    /**
     * @var array<TestCasesPreparator>
     */
    private array $preparators;

    private string $title;

    private Filters $filters;

    private Requester $requester;

    private LoggerInterface $logger;

    /**
     * @var \Closure[]
     */
    private array $beforeTestCaseCallbacks = [];

    /**
     * @var \Closure[]
     */
    private array $afterTestCaseCallbacks = [];

    /**
     * @param array<TestCasesPreparator> $preparators
     */
    public function __construct(
        string $title,
        Api $api,
        array $preparators,
        Requester $requester,
        ?Filters $filters = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct('', $title);
        $this->title = $title;
        $this->api = $api;
        $this->preparators = $preparators;
        $this->requester = $requester;
        $this->logger = $logger ?? new NullLogger();
        $this->filters = $filters ?? new Filters([], []);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function run(TestResult $result = null): TestResult
    {
        $this->prepareTestCases();

        return parent::run($result);
    }

    public function getName(): string
    {
        return $this->title;
    }

    /**
     * @throws ClientExceptionInterface
     * @noinspection PhpRedundantVariableDocTypeInspection
     */
    private function prepareTestCases(): void
    {
        foreach ($this->preparators as $preparator) {
            /** @var Operations $operations */
            $operations = $this->api->getOperations()
                ->map(
                    fn (Operation $op) => $op->setPreparator($preparator::getName())
                )
            ;
            try {
                $tests = $preparator->prepare($operations->filter([$this, 'includes']));
                foreach ($tests as $testCase) {
                    $testCase->setName('assert');
                    $testCase->setRequester($this->requester);
                    $testCase->setLogger($this->logger);
                    $testCase->setBeforeCallbacks($this->beforeTestCaseCallbacks);
                    $testCase->setAfterCallbacks($this->afterTestCaseCallbacks);
                    $testCase->prepare();
                    $this->addTest($testCase);
                }
            } catch (PreparatorLoadingException $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    public function setRequester(Requester $requester): void
    {
        $this->requester = $requester;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function includes(Operation $operation): bool
    {
        $include = true;
        foreach ($this->filters->getInclude() as $item) {
            $include = true;
            foreach ($item as $key => $value) {
                if (!$operation->has($key, $value)) {
                    $include = false;
                    continue 2;
                }
            }
            break;
        }

        if (!$include) {
            return false;
        }

        foreach ($this->filters->getExclude() as $item) {
            foreach ($item as $key => $value) {
                if (!$operation->has($key, $value)) {
                    continue 2;
                }
            }
            $include = false;
            break;
        }

        return $include;
    }

    /**
     * @param \Closure[] $callbacks
     */
    public function setBeforeTestCaseCallbacks(array $callbacks): void
    {
        $this->beforeTestCaseCallbacks = $callbacks;
    }

    /**
     * @param \Closure[] $callbacks
     */
    public function setAfterTestCaseCallbacks(array $callbacks): void
    {
        $this->afterTestCaseCallbacks = $callbacks;
    }
}
