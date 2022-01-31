<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use OpenAPITesting\Config\FiltersConfig;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Preparator\Exception\PreparatorLoadingException;
use OpenAPITesting\Preparator\TestCasesPreparator;
use OpenAPITesting\Requester\Requester;
use OpenAPITesting\Util\Traits\TimeBoundTrait;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 * @coversNothing
 */
final class Suite implements Test
{
    use TimeBoundTrait;

    private Api $api;

    /**
     * @var array<TestCasesPreparator>
     */
    private array $preparators;

    /**
     * @var array<string, Result>
     */
    private array $results = [];

    private string $title;

    private FiltersConfig $filters;

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
        ?FiltersConfig $filters = null,
        ?LoggerInterface $logger = null
    ) {
        $this->title = $title;
        $this->api = $api;
        $this->preparators = $preparators;
        $this->requester = $requester;
        $this->logger = $logger ?? new NullLogger();
        $this->filters = $filters ?? new FiltersConfig([], []);
    }

    public function launch(): void
    {
        $this->startedAt = Carbon::now();
        $this->logger->info("[{$this->startedAt->format('Y-m-d H:i:s')}] suite {$this->getName()} started.");
        $testCases = $this->prepareTestCases();
        $this->launchTestCases($testCases);
        $this->results = $this->getTestCasesResults($testCases);
        $this->finishedAt = Carbon::now();
        $this->logger->info("[{$this->finishedAt->format('Y-m-d H:i:s')}] suite {$this->getName()} finished.");
    }

    /**
     * @inheritDoc
     */
    public function getResult(): array
    {
        return $this->results;
    }

    public function getName(): string
    {
        return $this->title;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setRequester(Requester $requester): void
    {
        $this->requester = $requester;
    }

    public function includes(TestCase $testCase): bool
    {
        $include = true;
        if (\count($this->filters->getIncludedGroups()) > 0) {
            $include = \count(array_intersect($this->filters->getIncludedGroups(), $testCase->getGroups())) > 0;
        }

        if (\count(array_intersect($this->filters->getExcludedGroups(), $testCase->getGroups())) > 0) {
            $include = false;
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

    /**
     * @throws PreparatorLoadingException
     *
     * @return TestCase[]
     */
    private function prepareTestCases(): array
    {
        $testCases = [];
        foreach ($this->preparators as $preparator) {
            foreach ($preparator->prepare($this->api) as $testCase) {
                if ($this->includes($testCase)) {
                    $testCases[] = $testCase;
                }
            }
        }

        return $testCases;
    }

    /**
     * @param TestCase[] $testCases
     *
     * @throws PreparatorLoadingException
     * @throws ClientExceptionInterface
     */
    private function launchTestCases(array $testCases): void
    {
        foreach ($testCases as $testCase) {
            $testCase->setRequester($this->requester);
            $testCase->setLogger($this->logger);
            $testCase->setBeforeCallbacks($this->beforeTestCaseCallbacks);
            $testCase->setAfterCallbacks($this->afterTestCaseCallbacks);
            $testCase->launch();
        }
    }

    /**
     * @param TestCase[] $testCases
     *
     * @return array<string, Result>
     */
    private function getTestCasesResults(array $testCases): array
    {
        $results = [];
        foreach ($testCases as $testCase) {
            $results += $testCase->getResult();
        }

        return $results;
    }
}
