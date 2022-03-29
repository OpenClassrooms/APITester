<?php

declare(strict_types=1);

namespace APITester\Definition\Loader\Exception;

final class DefinitionLoaderNotFoundException extends \Exception
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = "Invalid loader format : {$message}";
    }
}
