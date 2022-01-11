<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator\Exception;

use Throwable;

final class PreparatorLoadingException extends \Exception
{
    public function __construct(string $preparator, Throwable $previous = null)
    {
        parent::__construct("Unable to load preparator '{$preparator}'", 0, $previous);
    }
}
