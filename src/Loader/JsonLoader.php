<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

use OpenAPITesting\Loader;
use OpenAPITesting\Util\Array_;

class JsonLoader implements Loader
{
    /**
     * @throws \JsonException
     */
    public function load($data): array
    {
        $list = (array) $data;

        $content = [];
        foreach ($list as $item) {
            $content[] = json_decode($item, true, 512, JSON_THROW_ON_ERROR);
        }

        return Array_::merge(...$content);
    }
}
