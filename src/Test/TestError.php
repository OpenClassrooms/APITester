<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

final class TestError implements \JsonSerializable
{
    private ?string $code;

    private string $message;

    public function __construct(string $message, string $code = null)
    {
        $this->message = $message;
        $this->code = $code;
    }

    public function __toString(): string
    {
        return null !== $this->code ? $this->code . ': ' . $this->message : $this->message;
    }

    /**
     * @return array{'code': ?string, 'message': string}
     */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}
