<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Unit\src\Loader\Fixture;

use OpenAPITesting\Fixture\OpenApiTestSuiteFixture;
use OpenAPITesting\Loader\Fixture\OpenApiExampleFixtureLoader;
use OpenAPITesting\Loader\OpenApiLoader;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class OpenApiExampleFixtureLoaderTest extends TestCase
{
    public const FIXTURE_LOCATION = __DIR__ . '/../../../fixtures/expected.json';
    public const OPENAPI_LOCATION = __DIR__ . '/../../../fixtures/openapi.yaml';

    private Serializer $serializer;

    /**
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
     * @throws \cebe\openapi\exceptions\IOException
     * @throws \cebe\openapi\exceptions\TypeErrorException
     */
    public function testInvoke(): void
    {
        $openApi = (new OpenApiLoader())(self::OPENAPI_LOCATION);
        /** @var OpenApiTestSuiteFixture $fixture */
        $fixture = $this->cleanTestSuiteFixture((new OpenApiExampleFixtureLoader())($openApi));

        $expectedFixtureRaw = file_get_contents(self::FIXTURE_LOCATION);
        if (false === $expectedFixtureRaw) {
            throw new \InvalidArgumentException('Couldn\'t read fixtures from file ' . self::FIXTURE_LOCATION);
        }
        $expectedFixture = $this->deserializeTestSuiteFixture($expectedFixtureRaw);

        Assert::assertEquals(
            $expectedFixture->getOperationTestCaseFixtures(),
            $fixture->getOperationTestCaseFixtures()
        );
    }

    protected function setUp(): void
    {
        $this->serializer = new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()]
        );
    }

    private function cleanTestSuiteFixture(OpenApiTestSuiteFixture $fixture): object
    {
        return $this->deserializeTestSuiteFixture(
            $this->serializer->serialize(
                $fixture,
                'json'
            )
        );
    }

    private function deserializeTestSuiteFixture(string $json): OpenApiTestSuiteFixture
    {
        /** @var OpenApiTestSuiteFixture $result */
        $result = $this->serializer->deserialize(
            $json,
            OpenApiTestSuiteFixture::class,
            'json'
        );

        return $result;
    }
}
