<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class Plan
{
    /**
     * @var Suite[]
     */
    private array $suites;

    private ?object $callbackObject;

    /**
     * @param Suite[] $suites
     */
    public function __construct(array $suites, ?object $callbackObject = null)
    {
        $this->suites = $suites;
        $this->callbackObject = $callbackObject;
    }

    /**
     * @return Suite[]
     */
    public function getSuites(): array
    {
        return $this->suites;
    }

    /**
     * @param array{beforeTestCase?: string[], afterTestCase?: string[]} $allCallbacks
     *
     * @return array{beforeTestCase?: \Closure[], afterTestCase?: \Closure[]}
     */
    private function callableFromConfig(array $allCallbacks): array
    {
        $closures = [];
        foreach ($allCallbacks as $type => $callbacks) {
            foreach ($callbacks as $callback) {
                if (null !== $this->callbackObject) {
                    $callback = [$this->callbackObject, $callback];
                }
                /** @var callable $callback */
                $closures[$type][] = \Closure::fromCallable($callback);
            }
        }

        return $closures;
    }
}
