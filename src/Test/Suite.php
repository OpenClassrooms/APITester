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
use APITester\Util\Filterable;
use APITester\Util\Traits\TimeBoundTrait;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\TestSuite;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Yaml\Tag\TaggedValue;

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
     * @param array<array<string, string>> $includeFilters
     * @param array<array<string, string>> $excludeFilters
     */
    public function includes(Filterable $object, array $includeFilters = [], array $excludeFilters = []): bool
    {
        $include = true;
        foreach ($includeFilters as $item) {
            $include = true;
            foreach ($item as $key => $value) {
                [$operator, $value] = $this->handleTags($value);
                if (!$object->has($key, $value, $operator)) {
                    $include = false;
                    continue 2;
                }
            }
            break;
        }

        if (!$include) {
            return false;
        }

        foreach ($excludeFilters as $item) {
            foreach ($item as $key => $value) {
                [$operator, $value] = $this->handleTags($value);
                if (!$object->has($key, $value, $operator)) {
                    continue 2;
                }
            }
            $include = false;
            break;
        }

        return $include;
    }

    /**
     * @param array<array<string, string>> $filter
     *
     * @return array<array<string, string>>
     */
    public function toTestCaseFilter(array $filter): array
    {
        /** @var array<array<string, string>> */
        return collect($filter)
            ->map(
                fn ($value) => collect($value)
                    ->filter(fn ($value, $key) => str_starts_with(
                        $key,
                        'testcase.'
                    ))
                    ->mapWithKeys(
                        fn ($value, $key) => [
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

    public function setPart(?string $part): void
    {
        $this->part = $part;
    }

    private function prepareTestCases(): void
    {
        /** @var Collection<int, TestCase> $allTests */
        $allTests = collect();
        foreach ($this->preparators as $preparator) {
            $operations = $this->api->getOperations()
                ->map(
                    fn (Operation $op) => $op->setPreparator($preparator::getName())
                )
            ;
            try {
                $operations = $this->filterOperation($operations);
                $tests = $preparator->doPrepare($operations);
                if (!$this->ignoreBaseLine) {
                    $tests = $this->filterTestCases($tests);
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
        return $operations->filter(
            fn (Operation $operation) => $this->includes(
                $operation,
                $this->filters->getInclude(),
                $this->filters->getExclude(),
            )
        );
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

        return collect($tests)->filter(fn (TestCase $test) => !\in_array(
            $test->getName(),
            $excludedTests,
            true
        ));
    }

    private function indexInPart(?string $part, int $index, int $total): bool
    {
        if (null === $part) {
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

    /**
     * @return array{0: string, 1: string|int}
     */
    private function handleTags(string|int|\Symfony\Component\Yaml\Tag\TaggedValue $value): array
    {
        $operator = '=';
        if ($value instanceof TaggedValue) {
            if ('NOT' === $value->getTag()) {
                $operator = '!=';
            }
            $value = $value->getValue();
        }

        return [$operator, $value];
    }
}
