<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

use Nelmio\Alice\DataLoaderInterface;
use Nelmio\Alice\Loader\NativeLoader;
use OpenAPITesting\Loader;
use OpenAPITesting\Fixture\OpenApiTestPlanFixture;

class AliceFixtureLoader implements Loader
{
    /**
     * @var NativeLoader
     */
    private $loader;

    public function __construct(?DataLoaderInterface $loader = null)
    {
        $this->loader = $loader ?? new NativeLoader();
    }

    /**
     * @throws \Nelmio\Alice\Throwable\LoadingThrowable
     */
    public function load($data): OpenApiTestPlanFixture
    {
        return new OpenApiTestPlanFixture($this->loader->loadData($data)->getObjects());
    }
}
