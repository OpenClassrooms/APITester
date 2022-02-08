<?php

declare(strict_types=1);

namespace OpenAPITesting\Config\Loader;

use OpenAPITesting\Config\Exception\ConfigurationException;
use OpenAPITesting\Config\Plan;
use OpenAPITesting\Util\Yaml;
use Symfony\Component\Dotenv\Dotenv;

final class PlanConfigLoader
{
    /**
     * @throws ConfigurationException
     */
    public static function load(string $path): Plan
    {
        $content = file_get_contents($path);
        if (false === $content) {
            throw new ConfigurationException("Could not load file '{$path}'");
        }
        $content = self::process($content);

        return Yaml::deserialize($content, Plan::class);
    }

    /**
     * @throws ConfigurationException
     */
    private static function process(string $content): string
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(PROJECT_DIR . '/env/.env');
        $patterns = [];
        $replacements = [];
        if (preg_match_all('/%env\((.+?)\)%/i', $content, $matches) > 0) {
            foreach ($matches[1] as $var) {
                $env = $_ENV[$var] ?? null;
                if (null === $env) {
                    throw new ConfigurationException("Environment variable '{$var}' is not defined.");
                }
                $patterns[] = "/%env\\({$var}\\)%/i";
                $replacements[] = $env;
            }
        }

        return (string) preg_replace($patterns, $replacements, $content);
    }
}
