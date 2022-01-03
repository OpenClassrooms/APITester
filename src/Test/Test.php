<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use DateTimeInterface;
use OpenAPITesting\Requester\Requester;
use Psr\Log\LoggerInterface;

interface Test
{
    /**
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function launch(): void;

    public function getStartedAt(): ?DateTimeInterface;

    public function getFinishedAt(): ?DateTimeInterface;

    /**
     * @return array<string, Result>
     */
    public function getResult(): array;

    public function getName(): string;

    public function setLogger(LoggerInterface $logger): void;

    public function setRequester(Requester $requester): void;
}
