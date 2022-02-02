<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use _PHPStan_daf7d5577\Symfony\Component\Console\Exception\LogicException;
use OpenAPITesting\Definition\Collection\Tokens;
use OpenAPITesting\Definition\Security;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2Security;
use OpenAPITesting\Definition\Token;

final class Error403TestCasesPreparator extends AuthorisationErrorTestCasesPreparator
{
    public static function getName(): string
    {
        return '403';
    }

    protected function getStatusCode(): int
    {
        return 403;
    }

    protected function getTestTokens(Security $security): Tokens
    {
        if ($security instanceof OAuth2Security) {
            return $this->tokens
                ->filter(
                    fn (Token $x) => 0 === $security
                        ->getScopes()
                        ->intersect($x->getScopes())
                        ->count()
                )
            ;
        }

        throw new LogicException('Unhandled security instance of type ' . \get_class($security));
    }
}
