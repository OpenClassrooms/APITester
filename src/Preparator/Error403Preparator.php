<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Tokens;
use APITester\Definition\Scope;
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
        if ($security->getScopes()->count() === 0) {
            return new Tokens();
        }

        if ($security instanceof OAuth2Security) {
            $tokens = $this->tokens
                ->filter(
                    fn (Token $x) => !\in_array($x->getName(), $this->config->excludedTokens, true)
                        && $security
                            ->getScopes()
                            ->select('name')
                            ->intersect($x->getScopes())
                            ->count() === 0
                )
            ;

            if ($tokens->count() === 0) {
                $this->logger->warning(
                    sprintf(
                        'No token with invalid scope for 403 found for security "%s" (scopes: %s), skipping 403 test generation.',
                        $security->getName(),
                        implode(
                            ', ',
                            array_map(
                                static fn (Scope $scope): string => $scope->getName(),
                                $security->getScopes()
                                    ->all()
                            )
                        )
                    )
                );

                return new Tokens();
            }

            return $tokens;
        }

        throw new \LogicException('Unhandled security instance of type ' . $security::class);
    }

    protected function getTestCaseName(): string
    {
        return 'DeniedToken';
    }
}
