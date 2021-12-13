<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

use Symfony\Component\Yaml\Yaml;

final class YamlLoader
{
    /**
     * @return array<int|string, mixed>
     */
    public function __invoke(string $data): array
    {
        return (array) Yaml::parse(file_get_contents($data));
    }
}
