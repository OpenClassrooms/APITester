<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class Plan
{
    /**
     * @var Suite[]
     */
    private array $suites;

    /**
     * @param Suite[] $suites
     */
    public function __construct(array $suites)
    {
        $this->suites = $suites;
    }

    /**
     * @return Suite[]
     */
    public function getSuites(): array
    {
        return $this->suites;
    }

    public function addBeforeTestCaseCallback(\Closure $callback): self
    {
        foreach ($this->suites as $suite) {
            $suite->addBeforeTestCaseCallback($callback);
        }

        return $this;
    }

    public function addAfterTestCaseCallback(\Closure $callback): self
    {
        foreach ($this->suites as $suite) {
            $suite->addAfterTestCaseCallback($callback);
        }

        return $this;
    }
}
