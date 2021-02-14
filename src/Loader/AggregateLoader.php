<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

use OpenAPITesting\Loader;

class AggregateLoader implements Loader
{
    /**
     * @var Loader[]
     */
    private array $loaders;

    public function __construct(Loader ...$loaders)
    {
        $this->loaders = $loaders;
    }

    /**
     * @return mixed
     */
    public function load($data)
    {
        foreach ($this->loaders as $loader) {
            $data = $loader->load($data);
        }

        return $data;
    }
}
