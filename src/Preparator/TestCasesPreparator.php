<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Body;
use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Tokens;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Token;
use APITester\Preparator\Config\PreparatorConfig;
use APITester\Preparator\Exception\InvalidPreparatorConfigException;
use APITester\Preparator\Exception\PreparatorLoadingException;
use APITester\Test\TestCase;
use APITester\Util\Json;
use APITester\Util\Object_;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

abstract class TestCasesPreparator
{
    protected Tokens $tokens;

    protected PreparatorConfig $config;

    public function __construct()
    {
        $this->tokens = new Tokens();
        $this->config = $this->newConfigInstance(static::getConfigFQCN());
    }

    /**
     * @param string[] $excludedFields
     */
    final public function buildTestCase(
        OperationExample $example,
        bool $auth = true,
        array $excludedFields = []
    ): TestCase {
        $operation = $example->getParent();

        if ($auth) {
            $example->authenticate($this->tokens);
        }

        return new TestCase(
            static::getName()
            . ' - '
            . ($operation !== null ? $operation->getId() : 'unknown_operation')
            . ' - '
            . $example->getName(),
            $example,
            $excludedFields,
        );
    }

    final public static function getName(): string
    {
        return lcfirst(
            str_replace(
                str_replace('TestCases', '', (new \ReflectionClass(self::class))->getShortName()),
                '',
                (new \ReflectionClass(static::class))->getShortName()
            )
        );
    }

    /**
     * @throws PreparatorLoadingException
     *
     * @return iterable<array-key, TestCase>
     */
    final public function doPrepare(Operations $operations): iterable
    {
        $testCases = $this->prepare($operations);
        foreach ($testCases as $testCase) {
            $testCase->addExcludedFields($this->config->excludedFields);
            $testCase->setSchemaValidation($this->config->schemaValidation);
        }

        return $testCases;
    }

    /**
     * @param array<mixed> $config
     *
     * @throws InvalidPreparatorConfigException
     */
    final public function configure(array $config): void
    {
        try {
            $config = $this->normalizeTaggedValues($config);
            $this->config = Object_::fromArray($config, static::getConfigFQCN());
        } catch (ExceptionInterface $e) {
            throw new InvalidPreparatorConfigException(static::class, 0, $e);
        }
    }

    final public function setTokens(Tokens $tokens): self
    {
        $this->tokens = $tokens;

        return $this;
    }

    final public function addToken(Token $token): self
    {
        $this->tokens->add($token);

        return $this;
    }

    final public function getConfig(): PreparatorConfig
    {
        return $this->config;
    }

    /**
     * @return class-string<PreparatorConfig>
     */
    protected static function getConfigFQCN(): string
    {
        $configClass = __NAMESPACE__ . '\\Config\\' . static::getConfigClassName();
        if (!class_exists($configClass)) {
            $configClass = PreparatorConfig::class;
        }

        /** @var class-string<PreparatorConfig> */
        return $configClass;
    }

    protected static function getConfigClassName(): string
    {
        return (new \ReflectionClass(static::class))->getShortName() . 'Config';
    }

    /**
     * @throws PreparatorLoadingException
     *
     * @return iterable<array-key, TestCase>
     */
    abstract protected function prepare(Operations $operations): iterable;

    protected function generateRandomBody(Body $request): ?string
    {
        return Json::encode(
            (array) (new SchemaFaker(
                $request->getSchema(),
                new Options(),
                true
            ))->generate()
        );
    }

    /**
     * @template T of PreparatorConfig
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function newConfigInstance(string $class)
    {
        return new $class();
    }

    /**
     * @param array<mixed> $config
     *
     * @return array<mixed>
     */
    private function normalizeTaggedValues(array $config): array
    {
        if (\array_key_exists('response', $config)
            && \array_key_exists('statusCode', $config['response'])
            && $config['response']['statusCode'] instanceof TaggedValue) {
            if ($config['response']['statusCode']->getTag() === 'NOT') {
                $statusCode = "#^(?!{$config['response']['statusCode']->getValue()})\\d+#";
            } else {
                $statusCode = $config['response']['statusCode']->getValue();
            }
            $config['response']['statusCode'] = $statusCode;
        }

        return $config;
    }
}
