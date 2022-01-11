<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Loader\Exception;

final class DefinitionLoadingException extends \Exception
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('', 0, $previous);
    }
}
