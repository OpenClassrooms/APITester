<?php

namespace OpenAPITesting\Gateways\Fixture;

use Nelmio\Alice\Loader\NativeLoader;
use OpenAPITesting\Models\Fixture\OperationTestSuiteFixture;

class OperationTestSuiteFixtureRepository implements OperationTestSuiteFixtureGateway
{
    private string $fixturesLocation;

    public function __construct(string $fixturesLocation)
    {
        $this->fixturesLocation = $fixturesLocation;
    }

    public function findAll(array $filters = []): array
    {
        $loader = new NativeLoader();
        $data = $loader->loadFile($this->fixturesLocation);

        /** @var OperationTestSuiteFixture[] $operationTestSuiteFixtures */
        $operationTestSuiteFixtures = array_filter($data->getObjects(), function ($object, $key) {
            if (get_class($object) === OperationTestSuiteFixture::class) {
                $object->setOperationId($key);

                return true;
            }

            return false;
        }, ARRAY_FILTER_USE_BOTH);

        $operationTestSuiteFixtures = $this->applyFilters($operationTestSuiteFixtures, $filters);

        return $operationTestSuiteFixtures;
    }

    /**
     * @param OperationTestSuiteFixture[] $operationTestSuiteFixtures
     * @return OperationTestSuiteFixture[]
     */
    private function applyFilters(array $operationTestSuiteFixtures, array $filters): array
    {
        return array_filter($operationTestSuiteFixtures, function (OperationTestSuiteFixture $operationTestSuiteFixture) use ($filters) {
            if (array_key_exists(self::FILTER_OPERATION_IDS, $filters)) {
                $operationIds = $filters[self::FILTER_OPERATION_IDS];
                if (!in_array($operationTestSuiteFixture->getOperationId(), $operationIds)) {
                    return false;
                }
            }

            return true;
        });
    }
}