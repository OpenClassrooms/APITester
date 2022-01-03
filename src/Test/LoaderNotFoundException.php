<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

final class LoaderNotFoundException extends \Exception
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = "Invalid loader format : {$message}";
    }
}
