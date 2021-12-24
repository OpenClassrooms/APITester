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
     * @var array<string, Error>
     */
    private array $errors = [];

    private string $title;

    /**
     * @param array<\OpenAPITesting\Test\Preparator\TestCasesPreparator> $preparators
     */
    public function __construct(
        string $title,
        OpenApi $openApi,
        array $preparators
    ) {
        $this->title = $title;
        $this->openApi = $openApi;
        $this->preparators = $preparators;
    }

    /**
     * @inheritDoc
     */
    public function launch(Requester $requester, ?LoggerInterface $logger = null): void
    {
        $logger ??= new NullLogger();
        $this->startedAt = Carbon::now();
        $logger->info("[{$this->startedAt->format('Y-m-d H:i:s')}] suite {$this->getTitle()} started.");
        $testCases = [];
        foreach ($this->preparators as $preparator) {
            $testCases[] = $preparator($this->openApi);
        }
        $testCases = array_merge(...$testCases);
        foreach ($testCases as $testCase) {
            $testCase->launch($requester, $logger);
            $this->errors += $testCase->getErrors();
        }
        $this->finishedAt = Carbon::now();
        $logger->info("[{$this->finishedAt->format('Y-m-d H:i:s')}] suite {$this->getTitle()} finished.");
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
