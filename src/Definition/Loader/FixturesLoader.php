<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Loader;

use OpenAPITesting\Definition\Collection\ExampleFixtures;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\ExampleFixture;
use OpenAPITesting\Definition\Loader\Exception\InvalidExampleFixturesException;
use OpenAPITesting\Util\Serializer;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final class FixturesLoader
{
    private ExampleFixtures $examples;

    public function __construct()
    {
        $this->examples = new ExampleFixtures();
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws InvalidExampleFixturesException
     */
    public function load(array $data): self
    {
        foreach ($data as $fixture) {
            try {
                /** @var ExampleFixture $example */
                $example = Serializer::create()
                    ->denormalize(
                        $fixture,
                        ExampleFixture::class
                    )
                ;
            } catch (ExceptionInterface $e) {
                throw new InvalidExampleFixturesException(static::class, 0, $e);
            }

            $this->addExample($example);
        }

        return $this;
    }

    public function addExample(ExampleFixture $example): self
    {
        $this->examples->add($example);

        return $this;
    }

    public function append(Operations $operations): Operations
    {
        //todo: implement append here

        return $operations;
    }

    public function getExamples(): ExampleFixtures
    {
        return $this->examples;
    }
}
