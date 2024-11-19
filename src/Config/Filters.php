<?php

declare(strict_types=1);

namespace APITester\Config;

use APITester\Util\Filterable;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

final class Filters
{
    /**
     * @var array<array<string, string>>
     */
    private array $include;

    /**
     * @var array<array<string, string>>
     */
    private array $exclude;

    private string $baseline = 'api-tester.baseline.yaml';

    /**
     * @param array<array<string, string>> $include
     * @param array<array<string, string>> $exclude
     */
    public function __construct(?array $include = null, ?array $exclude = null, string $baseline = null)
    {
        $this->include = $include ?? [];
        $this->exclude = $exclude ?? [];
        $this->baseline = $baseline ?? $this->baseline;
    }

    /**
     * @return array<array<string, string>>
     */
    public function getInclude(): array
    {
        return $this->include;
    }

    /**
     * @return array<array<string, string>>
     */
    public function getExclude(): array
    {
        return $this->exclude;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getBaseLineExclude(): array
    {
        if (!file_exists($this->getBaseline())) {
            return [];
        }

        $baseline = $this->getBaseLineData();

        if (!isset($baseline['exclude'])) {
            return [];
        }

        /** @var array<int, array<string, string>> */
        return $baseline['exclude'];
    }

    public function getBaseline(): string
    {
        return $this->baseline;
    }

    /**
     * @param array<array<string, string>> $include
     */
    public function addInclude(array $include): void
    {
        $this->include = array_merge($this->include, $include);
    }

    /**
     * @param array<array<string, string>> $exclude
     */
    public function addExclude(array $exclude): void
    {
        $this->exclude = array_merge($this->exclude, $exclude);
    }

    /**
     * @param array<int, array<string, string>> $exclude
     */
    public function writeBaseline(array $exclude): void
    {
        $exclude = collect($exclude)
            ->unique()
            ->values()
            ->toArray()
        ;
        if (file_exists($this->baseline)) {
            $exclude = [...$exclude, ...$this->getBaseLineExclude()];
        }
        file_put_contents(
            $this->baseline,
            Yaml::dump([
                'exclude' => $exclude,
            ])
        );
    }

    public function includes(Filterable $object): bool
    {
        $include = true;
        foreach ($this->getInclude() as $item) {
            $include = true;
            foreach ($item as $key => $value) {
                [$operator, $value] = $this->handleTags($value);
                if (!$object->has($key, $value, $operator)) {
                    $include = false;
                    continue 2;
                }
            }
            break;
        }

        if (!$include) {
            return false;
        }

        foreach ($this->getExclude() as $item) {
            foreach ($item as $key => $value) {
                [$operator, $value] = $this->handleTags($value);
                if (!$object->has($key, $value, $operator)) {
                    continue 2;
                }
            }
            $include = false;
            break;
        }

        return $include;
    }

    /**
     * @return array{'exclude': ?array<int, array<string, string>>}
     */
    private function getBaseLineData(): array
    {
        /** @var array{'exclude': ?array<int, array<string, string>>} */
        return Yaml::parseFile($this->getBaseline());
    }

    /**
     * @return array{0: string, 1: string|int}
     */
    private function handleTags(string|int|TaggedValue $value): array
    {
        $operator = '=';

        if ($value instanceof TaggedValue) {
            if ($value->getTag() === 'NOT') {
                $operator = '!=';
            }
            $value = (string) $value->getValue();
        }

        return [$operator, $value];
    }
}
