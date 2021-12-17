<?php

declare(strict_types=1);

namespace OpenAPITesting\Test\Loader;

use Nelmio\Alice\DataLoaderInterface;
use Nelmio\Alice\Loader\NativeLoader;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Test\TestSuite;

final class AliceTestSuiteLoader
{
    /**
     * @param array<array-key, mixed> $data
     *
     * @throws \Nelmio\Alice\Throwable\LoadingThrowable
     */
    public function __invoke(array $data, ?DataLoaderInterface $loader = null): TestSuite
    {
        $loader ??= new NativeLoader();
        $data = [
            TestCase::class => $data,
        ];

        $testCases = $this->getTestCaseFixtures($loader, $data);

        return new TestSuite($testCases);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws \Nelmio\Alice\Throwable\LoadingThrowable
     * @psalm-suppress MixedReturnTypeCoercion
     *
     * @return array<array-key, \OpenAPITesting\Test\TestCase>
     */
    private function getTestCaseFixtures(DataLoaderInterface $loader, array $data): array
    {
        return $loader->loadData($data)
            ->getObjects()
        ;
    }
}
