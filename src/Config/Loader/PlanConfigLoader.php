<?php

declare(strict_types=1);

namespace APITester\Config\Loader;

use APITester\Config\Exception\ConfigurationException;
use APITester\Config\Plan;
use APITester\Util\Yaml;
use Symfony\Component\Dotenv\Dotenv;

final class PlanConfigLoader
{
    /**
     * @throws ConfigurationException
     */
    public static function load(string $path): Plan
    {
        $content = file_get_contents($path);
        if ($content === false) {
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
                if ($env === null) {
                    throw new ConfigurationException("Environment variable '{$var}' is not defined.");
                }
                $patterns[] = "/%env\\({$var}\\)%/i";
                $replacements[] = $env;
            }
        }

        return (string) preg_replace($patterns, $replacements, $content);
    }
}
