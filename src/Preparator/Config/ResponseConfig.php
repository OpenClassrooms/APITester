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

    /**
     * @param string|TaggedValue|null $statusCode
     */
    public function setStatusCode($statusCode): void
    {
        if ($statusCode instanceof TaggedValue) {
            if ('NOT' === $statusCode->getTag()) {
                $statusCode = "#^(?!{$statusCode->getValue()})\\d+#";
            } else {
                $statusCode = $statusCode->getValue();
            }
        }
        $this->statusCode = $statusCode;
    }
}
