<?php

declare(strict_types=1);

namespace OpenAPITesting;

interface Loader
{
    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function load($data);
}
