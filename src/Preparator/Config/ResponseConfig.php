<?php

declare(strict_types=1);

namespace APITester\Preparator\Config;

use Symfony\Component\Yaml\Tag\TaggedValue;

final class ResponseConfig
{
    public ?string $statusCode = null;

    public string $body;

    /**
     * @var array<string, string>
     */
    public ?array $headers = null;

    public function getStatusCode(): ?string
    {
        return $this->statusCode;
    }

    public function setStatusCode(string|\Symfony\Component\Yaml\Tag\TaggedValue|null $statusCode): void
    {
        if ($statusCode instanceof TaggedValue) {
            if ($statusCode->getTag() === 'NOT') {
                $statusCode = "#^(?!{$statusCode->getValue()})\\d+#";
            } else {
                $statusCode = $statusCode->getValue();
            }
        }
        $this->statusCode = $statusCode;
    }
}