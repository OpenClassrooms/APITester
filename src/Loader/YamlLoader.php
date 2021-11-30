<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

use OpenAPITesting\Loader;
use OpenAPITesting\Util\Array_;
use Symfony\Component\Yaml\Yaml;

final class YamlLoader implements Loader
{
    public function load($data): array
    {
        $list = (array) $data;

        $content = [];
        foreach ($list as $item) {
            $content[] = Yaml::parse($item);
        }

        return Array_::merge(...$content);
    }
}
