<?php

declare(strict_types=1);

namespace APITester\Definition;

interface ApiSpecification
{
    public function getDocument(): mixed;
}
