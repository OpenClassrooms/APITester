<?php

declare(strict_types=1);

namespace OpenAPITesting\Test\Preparator;

use Nelmio\Alice\DataLoaderInterface;
use Nelmio\Alice\Loader\NativeLoader;
use OpenAPITesting\Test\TestCase;

final class AliceTestCasesLoader
{
    /**
     * @param array<array-key, mixed> $data
     *
     * @throws \Nelmio\Alice\Throwable\LoadingThrowable
     *
     * @return TestCase[]
     */
    public function __invoke(array $data, ?DataLoaderInterface $loader = null): array
    {
        $loader ??= new NativeLoader();
        $data = [
            TestCase::class => $data,
        ];

        return $this->getTestCaseFixtures($loader, $data);
    }

    public function getName(): string
    {
        return 'fixtures';
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
