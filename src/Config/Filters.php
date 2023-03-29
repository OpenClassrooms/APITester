<?php

declare(strict_types=1);

namespace APITester\Config;

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

    /**
     * @return array{'exclude': ?array<string, string>}
     */
    private function getBaseLineData(): array
    {
        /** @var array{'exclude': ?array<string, string>} */
        return Yaml::parseFile($this->getBaseline());
    }
}
