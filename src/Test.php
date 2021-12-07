<?php

declare(strict_types=1);

namespace OpenAPITesting;

interface Test
{
    public function launch(Requester $requester): void;

    /**
     * @return string[][]
     */
    public function getErrors(): array;
}
