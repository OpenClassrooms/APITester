<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader\Fixture;

use Nelmio\Alice\DataLoaderInterface;
use Nelmio\Alice\Loader\NativeLoader;
use OpenAPITesting\Fixture\OpenApiTestPlanFixture;
use OpenAPITesting\Fixture\OperationTestCaseFixture;

final class AliceFixtureLoader
{
    /**
     * @param array<array-key, mixed> $data
     * @throws \Nelmio\Alice\Throwable\LoadingThrowable
     */
    public function __invoke(array $data, ?DataLoaderInterface $loader = null): OpenApiTestPlanFixture
    {
        $loader ??= new NativeLoader();
        $data = [
            OperationTestCaseFixture::class => $data,
        ];

        return new OpenApiTestPlanFixture($loader->loadData($data)->getObjects());
    }
}
