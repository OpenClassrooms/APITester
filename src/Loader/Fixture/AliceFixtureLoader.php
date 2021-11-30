<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader\Fixture;

use Nelmio\Alice\DataLoaderInterface;
use Nelmio\Alice\Loader\NativeLoader;
use OpenAPITesting\Fixture\OpenApiTestPlanFixture;
use OpenAPITesting\Fixture\OperationTestCaseFixture;
use OpenAPITesting\Loader;

final class AliceFixtureLoader implements Loader
{
    private DataLoaderInterface $loader;

    public function __construct(?DataLoaderInterface $loader = null)
    {
        $this->loader = $loader ?? new NativeLoader();
    }

    /**
     * @param mixed $data
     *
     * @throws \Nelmio\Alice\Throwable\LoadingThrowable
     */
    public function load($data): OpenApiTestPlanFixture
    {
        $data = [
            OperationTestCaseFixture::class => $data,
        ];

        return new OpenApiTestPlanFixture($this->loader->loadData($data)->getObjects());
    }
}
