<?php

declare(strict_types=1);

namespace APITester\Schema\Entity;

interface ApiSpecification
{
    public function getDocument(): mixed;
}
