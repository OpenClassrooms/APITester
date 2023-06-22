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

    public function setStatusCode(string|TaggedValue|null $statusCode): void
    {
        if ($statusCode instanceof TaggedValue) {
            if ($statusCode->getTag() === 'NOT') {
                $value = (string) ($statusCode->getValue());
                $statusCode = "#^(?!{$value})\\d+#";
            } else {
                $statusCode = $statusCode->getValue();
            }
        } else {
            $this->statusCode = (string) $statusCode;
        }
    }
}
