<?php

declare(strict_types=1);

namespace APITester\Tests\Config;

use APITester\Config\Filters;
use APITester\Util\Filterable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Tag\TaggedValue;

final class FiltersTest extends TestCase
{
    private ?string $tempFile = null;

    protected function tearDown(): void
    {
        if ($this->tempFile !== null && file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testDefaultConstructorHasEmptyArrays(): void
    {
        $filters = new Filters();

        static::assertSame([], $filters->getInclude());
        static::assertSame([], $filters->getExclude());
    }

    public function testAddIncludeAppendsRules(): void
    {
        $filters = new Filters();
        $filters->addInclude([
            [
                'id' => 'foo',
            ],
        ]);
        $filters->addInclude([
            [
                'id' => 'bar',
            ],
        ]);

        static::assertCount(2, $filters->getInclude());
        static::assertSame('foo', $filters->getInclude()[0]['id']);
        static::assertSame('bar', $filters->getInclude()[1]['id']);
    }

    public function testAddExcludeAppendsRules(): void
    {
        $filters = new Filters();
        $filters->addExclude([
            [
                'method' => 'DELETE',
            ],
        ]);

        static::assertCount(1, $filters->getExclude());
    }

    public function testIncludesWithEmptyFiltersReturnsTrue(): void
    {
        $filters = new Filters();
        $object = $this->createFilterable([
            'id' => 'anything',
        ]);

        static::assertTrue($filters->includes($object));
    }

    public function testIncludesWithMatchingIncludeRule(): void
    {
        $filters = new Filters([
            [
                'id' => 'foo',
            ],
        ]);
        $matching = $this->createFilterable([
            'id' => 'foo',
        ]);
        $nonMatching = $this->createFilterable([
            'id' => 'bar',
        ]);

        static::assertTrue($filters->includes($matching));
        static::assertFalse($filters->includes($nonMatching));
    }

    public function testIncludesWithExcludeRule(): void
    {
        $filters = new Filters(null, [
            [
                'id' => 'excluded',
            ],
        ]);
        $excluded = $this->createFilterable([
            'id' => 'excluded',
        ]);
        $included = $this->createFilterable([
            'id' => 'other',
        ]);

        static::assertFalse($filters->includes($excluded));
        static::assertTrue($filters->includes($included));
    }

    public function testHandleTagsNotProducesNotEqual(): void
    {
        $ref = new \ReflectionMethod(Filters::class, 'handleTags');
        $ref->setAccessible(true);
        $filters = new Filters();

        /** @var array{0: string, 1: string|int|null} $result */
        $result = $ref->invoke($filters, new TaggedValue('NOT', 'DELETE'));

        static::assertSame('!=', $result[0]);
        static::assertSame('DELETE', $result[1]);
    }

    public function testHandleTagsInProducesContains(): void
    {
        $ref = new \ReflectionMethod(Filters::class, 'handleTags');
        $ref->setAccessible(true);
        $filters = new Filters();

        /** @var array{0: string, 1: string|int|null} $result */
        $result = $ref->invoke($filters, new TaggedValue('IN', 'pet'));

        static::assertSame('contains', $result[0]);
        static::assertSame('pet', $result[1]);
    }

    public function testHandleTagsNullStringConvertsToNull(): void
    {
        $ref = new \ReflectionMethod(Filters::class, 'handleTags');
        $ref->setAccessible(true);
        $filters = new Filters();

        /** @var array{0: string, 1: string|int|null} $result */
        $result = $ref->invoke($filters, 'null');

        static::assertSame('=', $result[0]);
        static::assertNull($result[1]);
    }

    public function testWriteBaselineAndGetBaselineExcludeRoundTrip(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'api-tester-test-') . '.yaml';
        $filters = new Filters(null, null, $this->tempFile);

        $exclude = [
            [
                'id' => 'test_1',
            ],
            [
                'id' => 'test_2',
            ],
        ];
        $filters->writeBaseline($exclude);

        $result = $filters->getBaseLineExclude();

        static::assertCount(2, $result);
        static::assertSame('test_1', $result[0]['id']);
        static::assertSame('test_2', $result[1]['id']);
    }

    /**
     * @param array<string, mixed> $props
     */
    private function createFilterable(array $props): Filterable
    {
        return new class($props) implements Filterable {
            /**
             * @param array<string, mixed> $props
             */
            public function __construct(
                private readonly array $props
            ) {
            }

            public function has(string $prop, $value, string $operator = '='): bool
            {
                if (!array_key_exists($prop, $this->props)) {
                    return false;
                }

                $propValue = $this->props[$prop];

                return match ($operator) {
                    '=' => $propValue === $value,
                    '!=' => $propValue !== $value,
                    'contains' => is_string($propValue) && is_string($value) && str_contains($propValue, $value),
                    default => false,
                };
            }
        };
    }
}
