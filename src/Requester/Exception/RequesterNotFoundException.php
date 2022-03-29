<?php

declare(strict_types=1);

namespace APITester\Requester\Exception;

final class RequesterNotFoundException extends \Exception
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = "Invalid requester name : {$message}";
    }
}
