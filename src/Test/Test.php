<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use DateTimeInterface;
use OpenAPITesting\Requester\Requester;

interface Test
{
    /**
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function launch(Requester $requester): void;

    public function getStartedAt(): ?DateTimeInterface;

    public function getFinishedAt(): ?DateTimeInterface;

    /**
     * @return array<string, Error>
     */
    public function getErrors(): array;

    public function getTitle(): string;
}
