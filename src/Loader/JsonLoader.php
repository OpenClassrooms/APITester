<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

use OpenAPITesting\Util\Json;

final class JsonLoader
{
    /**
     * @return array<int|string, mixed>
     * @throws \JsonException
     */
    public function __invoke(string $data): array
    {
        return Json::decode($data);
    }
}
