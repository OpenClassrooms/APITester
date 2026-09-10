<?php

declare(strict_types=1);

namespace APITester\Schema\Entity\Collection;

use APITester\Schema\Entity\Example\OperationExample;
use Illuminate\Support\Collection;

/**
 * @method OperationExample[] getIterator()
 * @method OperationExample   get(string $key, $default = null)
 * @extends Collection<array-key, OperationExample>
 */
final class OperationExamples extends Collection
{
    /**
     * @var OperationExample[]
     */
    protected array $examples;

    /**
     * @param OperationExample[] $examples
     */
    public function __construct(array $examples = [])
    {
        parent::__construct($examples);
    }
}
