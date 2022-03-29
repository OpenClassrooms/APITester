<?php

declare(strict_types=1);

namespace APITester\Definition\Loader\Exception;

final class DefinitionLoadingException extends \Exception
{
    public function __construct(string $msg = '', ?\Throwable $previous = null)
    {
        parent::__construct($msg, 0, $previous);
    }
}
