<?php

declare(strict_types=1);

namespace OpenAPITesting;

interface Loader
{
    /**
     * @return mixed
     */
    public function load($data);
}
