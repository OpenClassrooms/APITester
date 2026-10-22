<?php

declare(strict_types=1);

namespace APITester\Tests\Fixtures\Runner;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class Kernel extends BaseKernel
{
    public bool $fromParent = false;

    public function boot(): void
    {
    }

    public function registerBundles(): iterable
    {
        return [];
    }

    public function registerContainerConfiguration(mixed $loader): void
    {
    }

    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
    {
        return new Response('parent', $this->fromParent ? 200 : 500, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
