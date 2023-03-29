<?php

declare(strict_types=1);

namespace APITester\Test;

final class Result implements \JsonSerializable, \Stringable
{
    public const STATUS_FAILED = 'failed';

    public const STATUS_SUCCESS = 'success';

    private function __construct(private readonly string $status, private readonly string $message, private readonly ?string $code = null)
    {
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
