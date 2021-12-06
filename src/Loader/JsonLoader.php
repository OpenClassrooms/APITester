<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

use OpenAPITesting\Loader;
use OpenAPITesting\Util\Array_;
use function Psl\Json\decode;

final class JsonLoader implements Loader
{
    /**
     * @param string[]|string $data
     */
    public function load($data): array
    {
        $list = (array) $data;

        $content = [];
        foreach ($list as $item) {
            $content[] = decode($item);
        }

        return Array_::merge(...$content);
    }
}
