<?php

declare(strict_types=1);

namespace APITester\Config;

use APITester\Util\Path;

final class Plan
{
    private ?string $bootstrap = null;

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

    public function getBootstrap(): ?string
    {
        return $this->bootstrap;
    }

    public function setBootstrap(?string $bootstrap): void
    {
        $this->bootstrap = Path::getBasePath() . '/' . $bootstrap;
    }
}
