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
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\TestSuite;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 * @coversNothing
 * @template T of \PHPUnit\Framework\TestCase
 * @template K of \Symfony\Component\HttpKernel\KernelInterface
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
     * @var class-string<T>
     */
    private string $testCaseClass;

    /**
     * @var class-string<K>|null
     */
    private ?string $kernelClass;

    /**
     * @param array<TestCasesPreparator> $preparators
     * @param class-string<T>            $testCaseClass
     * @param class-string<K>|null       $kernelClass
     */
    public function __construct(
        string $title,
        Api $api,
        array $preparators,
        Requester $requester,
        ?Filters $filters = null,
        ?LoggerInterface $logger = null,
        string $testCaseClass = \PHPUnit\Framework\TestCase::class,
        ?string $kernelClass = null
    ) {
        parent::__construct('', $title);
        $this->title = $title;
        $this->api = $api;
        $this->preparators = $preparators;
        $this->requester = $requester;
        $this->logger = $logger ?? new NullLogger();
        $this->filters = $filters ?? new Filters([], []);
        $this->testCaseClass = $testCaseClass;
        $this->kernelClass = $kernelClass;
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
                    $testCase->setRequester($this->requester);
                    $testCase->setLogger($this->logger);
                    $testCase->setBeforeCallbacks($this->beforeTestCaseCallbacks);
                    $testCase->setAfterCallbacks($this->afterTestCaseCallbacks);
                    $testCase->prepare();
                    $this->addTest(
                        $testCase->toPhpUnitTestCase(
                            $this->testCaseClass,
                            $this->kernelClass
                        )
                    );
                }
            } catch (PreparatorLoadingException $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
