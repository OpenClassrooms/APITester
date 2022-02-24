<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Illuminate\Support\Collection;
use OpenAPITesting\Definition\RequestExample;

/**
 * @method RequestExample[] getIterator()
 * @extends Collection<array-key, RequestExample>
 */
final class RequestExamples extends Collection
{
    /**
     * @var RequestExample[]
     */
    protected $items;
}
