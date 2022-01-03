<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

final class Result implements \JsonSerializable
{
    public const STATUS_FAILED = 'failed';

    public const STATUS_SUCCESS = 'success';

    private ?string $code;

    private string $message;

    private string $status;

    private function __construct(string $status, string $message, string $code = null)
    {
        $this->status = $status;
        $this->message = $message;
        $this->code = $code;
    }

    public function __toString(): string
    {
        return null !== $this->code ? $this->code . ': ' . $this->message : $this->message;
    }

    public static function success(string $message = null, string $code = null): self
    {
        return new self(self::STATUS_SUCCESS, $message ?? 'Succeeded.', $code);
    }

    public static function failed(string $message, string $code = null): self
    {
        return new self(self::STATUS_FAILED, $message, $code);
    }

    public function hasSucceeded(): bool
    {
        return self::STATUS_SUCCESS === $this->status;
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

    public function getStatus(): string
    {
        return $this->status;
    }
}
