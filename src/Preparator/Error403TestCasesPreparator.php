<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Tokens;
use APITester\Definition\Security;
use APITester\Definition\Security\OAuth2\OAuth2Security;
use APITester\Definition\Token;

final class Error403TestCasesPreparator extends AuthorisationErrorTestCasesPreparator
{
    protected function getStatusCode(): int
    {
        return 403;
    }

    protected function getTestTokens(Security $security): Tokens
    {
        if (0 === $security->getScopes()->count()) {
            return new Tokens();
        }
        if ($security instanceof OAuth2Security) {
            return $this->tokens
                ->filter(
                    fn (Token $x) => 0 === $security
                        ->getScopes()
                        ->select('name')
                        ->intersect($x->getScopes())
                        ->count()
                )
            ;
        }

        throw new \LogicException('Unhandled security instance of type ' . \get_class($security));
    }
}
