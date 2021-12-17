<?php

declare(strict_types=1);

namespace OpenAPITesting;

use DateTimeInterface;

interface Test
{
    public function launch(): void;

    public function getStartedAt(): ?DateTimeInterface;

    public function getFinishedAt(): ?DateTimeInterface;
}
