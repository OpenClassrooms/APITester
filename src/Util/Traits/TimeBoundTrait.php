<?php

declare(strict_types=1);

namespace OpenAPITesting\Util\Traits;

use DateTimeInterface;

trait TimeBoundTrait
{
    private ?DateTimeInterface $startedAt = null;

    private ?DateTimeInterface $finishedAt = null;

    public function getStartedAt(): ?DateTimeInterface
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?DateTimeInterface
    {
        return $this->finishedAt;
    }
}
