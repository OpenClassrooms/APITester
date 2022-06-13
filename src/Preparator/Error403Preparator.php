<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Tokens;
use APITester\Definition\Security;
use APITester\Definition\Security\OAuth2\OAuth2Security;
use APITester\Definition\Token;
use APITester\Preparator\Config\Error403PreparatorConfig;

/**
 * @property Error403PreparatorConfig $config
 */
final class Error403Preparator extends SecurityErrorPreparator
{
    protected function getStatusCode(): string
    {
        return '403';
    }

    protected function getTestTokens(Security $security): Tokens
    {
        if (0 === $security->getScopes()->count()) {
            return new Tokens();
        }

        if ($security instanceof OAuth2Security) {
            $tokens = $this->tokens
                ->filter(
                    fn (Token $x) => !\in_array($x->getName(), $this->config->excludedTokens, true)
                        && 0 === $security
                            ->getScopes()
                            ->select('name')
                            ->intersect($x->getScopes())
                            ->count()
                )
            ;

            if (0 === $tokens->count()) {
                throw new \LogicException('No token with invalid scope for 403 found.');
            }

            return $tokens;
        }

        throw new \LogicException('Unhandled security instance of type ' . \get_class($security));
    }

    protected function getTestCaseName(): string
    {
        return 'DeniedToken';
    }
}
