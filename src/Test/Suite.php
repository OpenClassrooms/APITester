<?php

declare(strict_types=1);

namespace APITester\Test;

use APITester\Config\Filters;
use APITester\Definition\Api;
use APITester\Definition\Collection\Operations;
use APITester\Definition\Operation;
use APITester\Preparator\Exception\PreparatorLoadingException;
use APITester\Preparator\TestCasesPreparator;
use APITester\Requester\Requester;
use APITester\Util\Traits\TimeBoundTrait;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\TestSuite;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 * @coversNothing
 * @template T of \PHPUnit\Framework\TestCase
 * @template K of \Symfony\Component\HttpKernel\HttpKernelInterface
 */
final class Suite extends TestSuite
{
    use TimeBoundTrait;

    private readonly string $title;

    /**
     * @var \Closure[]
     */
    private array $beforeTestCaseCallbacks = [];

    /**
     * @var \Closure[]
     */
    private array $afterTestCaseCallbacks = [];

    private bool $ignoreBaseLine = false;

    private bool $onlyBaseLine = false;

    private ?string $part = null;

    /**
     * @param array<TestCasesPreparator> $preparators
     * @param class-string<T>            $testCaseClass
     */
    public function __construct(
        string $title,
        private readonly Api $api,
        private readonly array $preparators,
        private Requester $requester,
        private readonly Filters $filters = new Filters([], []),
        private LoggerInterface $logger = new NullLogger(),
        private readonly string $testCaseClass = \PHPUnit\Framework\TestCase::class
    ) {
        parent::__construct('', $title);
        $this->title = $title;
    }

    public function run(TestResult $result = null): TestResult
    {
        $this->prepareTestCases();

        return parent::run($result);
    }

    public function getName(): string
    {
        return $this->title;
    }

    public function setRequester(Requester $requester): void
    {
        $this->requester = $requester;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param array<array<string, string>> $filter
     *
     * @return array<iterable<string, string>>
     */
    public function toTestCaseFilter(array $filter): array
    {
        /** @var array<iterable<string, string>> */
        return collect($filter)
            ->map(
                static fn ($value) => collect($value)
                    ->filter(static fn ($value, $key) => str_starts_with(
                        $key,
                        'testcase.'
                    ))
                    ->mapWithKeys(
                        static fn ($value, $key) => [
                            str_replace('testcase.', '', $key) => $value,
                        ]
                    )
            )
            ->filter()
            ->toArray()
        ;
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

    public function setIgnoreBaseLine(bool $ignoreBaseLine): void
    {
        $this->ignoreBaseLine = $ignoreBaseLine;
    }

    public function setOnlyBaseLine(bool $onlyBaseLine): void
    {
        $this->onlyBaseLine = $onlyBaseLine;
    }

    public function setPart(?string $part): void
    {
        $this->part = $part;
    }

    private function prepareTestCases(): void
    {
        /** @var Collection<int, TestCase> $allTests */
        $allTests = collect();
        foreach ($this->preparators as $preparator) {
            $preparator->setLogger($this->logger);
            $operations = $this->api->getOperations()
                ->map(
                    static fn (Operation $op) => $op->setPreparator($preparator::getName())
                )
            ;
            try {
                $operations = $this->filterOperation($operations);
                $tests = $preparator->doPrepare($operations);
                if (!$this->ignoreBaseLine) {
                    $tests = $this->filterTestCases($tests);
                }
                if ($this->onlyBaseLine) {
                    $tests = $this->filterOnlyTestCases($tests);
                }
                foreach ($tests as $testCase) {
                    $testCase->setRequester($this->requester);
                    $testCase->setLogger($this->logger);
                    $testCase->setBeforeCallbacks($this->beforeTestCaseCallbacks);
                    $testCase->setAfterCallbacks($this->afterTestCaseCallbacks);
                    $allTests->add($testCase);
                }
            } catch (PreparatorLoadingException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $allTests
            ->sortBy('operation')
            ->values()
            ->filter(fn (TestCase $testCase, int $index) => $this->indexInPart(
                $this->part,
                $index,
                $allTests->count(),
            ))
            ->each(
                fn (TestCase $testCase) => $this->addTest(
                    $testCase->toPhpUnitTestCase(
                        $this->testCaseClass,
                    )
                )
            )
        ;
    }

    private function filterOperation(Operations $operations): Operations
    {
        return $operations->filter(fn (Operation $operation) => $this->filters->includes($operation));
    }

    /**
     * @param iterable<array-key, TestCase> $tests
     *
     * @return iterable<array-key, TestCase>
     */
    private function filterTestCases(iterable $tests): iterable
    {
        $excludedTests = array_column(
            $this->toTestCaseFilter($this->filters->getBaseLineExclude()),
            'name'
        );

        return collect($tests)->filter(static fn (TestCase $test) => !\in_array(
            $test->getName(),
            $excludedTests,
            true
        ));
    }

    /**
     * @param iterable<array-key, TestCase> $tests
     *
     * @return iterable<array-key, TestCase>
     */
    private function filterOnlyTestCases(iterable $tests): iterable
    {
        $includedTests = array_column(
            $this->toTestCaseFilter($this->filters->getBaseLineExclude()),
            'name'
        );

        return collect($tests)->filter(static fn (TestCase $test) => \in_array(
            $test->getName(),
            $includedTests,
            true
        ));
    }

    private function indexInPart(?string $part, int $index, int $total): bool
    {
        if ($part === null) {
            return true;
        }

        [$partIndex, $partsCount] = explode('/', $part);

        $partIndex = (int) $partIndex;
        $partsCount = (int) $partsCount;

        if ($partsCount > 0 && $index <= $total) {
            $span = (int) ceil($total / $partsCount);
            $from = $span * ($partIndex - 1);
            $to = $span * $partIndex;

            return $from <= $index && $index < $to;
        }

        return false;
    }
}
