<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use cebe\openapi\spec\OpenApi;
use OpenAPITesting\Requester\Requester;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 * @coversNothing
 */
final class Suite implements Test
{
    use TimeBoundTrait;

    private OpenApi $openApi;

    /**
     * @var array<\OpenAPITesting\Test\Preparator\TestCasesPreparator>
     */
    private array $preparators;

    /**
     * @var array<string, Result>
     */
    private array $results = [];

    private string $title;

    private Filters $filters;

    private Requester $requester;

    private LoggerInterface $logger;

    private ?\Closure $beforeTestCaseCallback = null;

    private ?\Closure $afterTestCaseCallback = null;

    /**
     * @param array<\OpenAPITesting\Test\Preparator\TestCasesPreparator> $preparators
     */
    public function __construct(
        string $title,
        OpenApi $openApi,
        array $preparators,
        Requester $requester,
        ?Filters $filters = null,
        ?LoggerInterface $logger = null
    ) {
        $this->title = $title;
        $this->openApi = $openApi;
        $this->preparators = $preparators;
        $this->requester = $requester;
        $this->logger = $logger ?? new NullLogger();
        $this->filters = $filters ?? new Filters([], []);
    }

    /**
     * @inheritDoc
     */
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

    public function setBeforeTestCaseCallback(?\Closure $beforeTestCaseCallback): void
    {
        $this->beforeTestCaseCallback = $beforeTestCaseCallback;
    }

    public function setAfterTestCaseCallback(?\Closure $afterTestCaseCallback): void
    {
        $this->afterTestCaseCallback = $afterTestCaseCallback;
    }

    /**
     * @param TestCase[] $testCases
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    private function launchTestCases(array $testCases): void
    {
        foreach ($testCases as $testCase) {
            $testCase->setRequester($this->requester);
            $testCase->setLogger($this->logger);
            $testCase->setBeforeCallback($this->beforeTestCaseCallback);
            $testCase->setAfterCallback($this->afterTestCaseCallback);
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

    /**
     * @return TestCase[]
     */
    private function prepareTestCases(): array
    {
        $testCases = [];
        foreach ($this->preparators as $preparator) {
            $testCases[] = array_filter(
                $preparator($this->openApi),
                [$this, 'includes']
            );
        }

        return array_merge(...$testCases);
    }
}
