<?php

declare(strict_types=1);

namespace APITester\Definition\Loader;

use APITester\Definition\Api;
use APITester\Definition\Body;
use APITester\Definition\Collection\Bodies;
use APITester\Definition\Collection\OperationExamples;
use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Parameters;
use APITester\Definition\Collection\Responses;
use APITester\Definition\Collection\Scopes;
use APITester\Definition\Collection\Securities;
use APITester\Definition\Collection\Servers;
use APITester\Definition\Collection\Tags;
use APITester\Definition\Example\BodyExample;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Loader\Exception\DefinitionLoadingException;
use APITester\Definition\Loader\Exception\InvalidExampleException;
use APITester\Definition\OpenApiSpecification;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\Response;
use APITester\Definition\Security\ApiKeySecurity;
use APITester\Definition\Security\HttpSecurity;
use APITester\Definition\Security\OAuth2\OAuth2AuthorizationCodeSecurity;
use APITester\Definition\Security\OAuth2\OAuth2ClientCredentialsSecurity;
use APITester\Definition\Security\OAuth2\OAuth2ImplicitSecurity;
use APITester\Definition\Security\OAuth2\OAuth2PasswordSecurity;
use APITester\Definition\Server;
use APITester\Definition\Tag;
use APITester\Util\Json;
use cebe\openapi\Reader;
use cebe\openapi\spec\Example;
use cebe\openapi\spec\Header;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OAuthFlow;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\SecurityRequirement;
use cebe\openapi\spec\SecurityScheme;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class OpenApiDefinitionLoader implements DefinitionLoader
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function load(string $filePath, string $format = self::FORMAT_YAML, array $filters = []): Api
    {
        if (!\in_array($format, self::FORMATS, true)) {
            throw new \InvalidArgumentException('Invalid format ' . $format);
        }
        try {
            /** @var OpenApi $openApi */
            $openApi = Reader::readFromYamlFile($filePath);
            $api = Api::create();
        } catch (\Exception $e) {
            throw new DefinitionLoadingException("Could not load {$filePath}", $e);
        }

        /** @var array<string, SecurityScheme> $securitySchemes */
        $securitySchemes = $openApi->components !== null ? $openApi->components->securitySchemes : [];

        return $api
            ->setOperations(
                $this->getOperations(
                    $openApi->paths->getPaths(),
                    $securitySchemes,
                    $filters
                )
            )
            ->setServers($this->getServers($openApi->servers))
            ->setTags($this->getTags($openApi->tags))
            ->setSpecification(new OpenApiSpecification($openApi))
        ;
    }

    public static function getFormat(): string
    {
        return 'openapi';
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param array<string, string[]>       $filters
     * @param array<string, SecurityScheme> $securitySchemes
     * @param array<string, PathItem>       $paths
     *
     * @throws DefinitionLoadingException
     */
    private function getOperations(array $paths, array $securitySchemes, array $filters = []): Operations
    {
        $operations = new Operations();
        foreach ($paths as $path => $pathInfo) {
            foreach ($pathInfo->getOperations() as $method => $operation) {
                if (isset($filters['operationId'])
                    && !in_array($operation->operationId, $filters['operationId'], true)) {
                    continue;
                }
                /** @var \cebe\openapi\spec\Parameter[] $parameters */
                $parameters = array_merge($operation->parameters ?? [], $pathInfo->parameters ?? []);
                /** @var RequestBody $requestBody */
                $requestBody = $operation->requestBody;
                $responses = $operation->responses;
                $requirements = $this->getSecurityRequirementsScopes($operation->security ?? []);

                $operations->add(
                    Operation::create(
                        $operation->operationId ?? $this->generateOperationId($path, $method),
                        $path
                    )
                        ->setMethod($method)
                        ->setSummary($operation->summary ?? '')
                        ->setDescription($operation->description ?? '')
                        ->setPathParameters($this->getParameters($parameters, 'path'))
                        ->setQueryParameters($this->getParameters($parameters, 'query'))
                        ->setHeaders($this->getParameters($parameters, 'header'))
                        ->setRequestBodies($this->getRequests($requestBody))
                        ->setResponses($this->getResponses($responses))
                        ->setTags($this->getTags($operation->tags))
                        ->setSecurities($this->getSecurities($securitySchemes, $requirements))
                        ->setExamples($this->getExamples($operation, $parameters))
                        ->setExtensions($operation->getExtensions())
                );
            }
        }

        return $operations;
    }

    /**
     * @param \cebe\openapi\spec\Server[] $servers
     */
    private function getServers(array $servers): Servers
    {
        $collection = [];
        foreach ($servers as $server) {
            $collection[] = new Server($server->url);
        }

        return new Servers($collection);
    }

    /**
     * @param \cebe\openapi\spec\Tag[]|string[] $tags
     */
    private function getTags(array $tags): Tags
    {
        $collection = [];

        foreach ($tags as $tag) {
            if (!\is_string($tag)) {
                $tag = $tag->name;
            }
            $collection[] = new Tag($tag);
        }

        return new Tags($collection);
    }

    /**
     * @param SecurityRequirement[] $securityRequirements
     *
     * @return array<string, string[]>
     */
    private function getSecurityRequirementsScopes(array $securityRequirements): array
    {
        $requirements = [];
        foreach ($securityRequirements as $requirement) {
            /**
             * @var string   $name
             * @var string[] $data
             */
            foreach ((array) $requirement->getSerializableData() as $name => $data) {
                $requirements[$name] = $data;
            }
        }

        return $requirements;
    }

    private function generateOperationId(string $path, string $method): string
    {
        return trim(str_replace('/', '_', $path) . '_' . $method, '_');
    }

    /**
     * @param \cebe\openapi\spec\Parameter[] $parameters
     */
    private function getParameters(array $parameters, string $in): Parameters
    {
        $collection = new Parameters();
        foreach ($parameters as $name => $parameter) {
            /** @var Schema|null $schema */
            $schema = $parameter->schema;
            if ($parameter->in !== $in) {
                continue;
            }
            $defParam = Parameter::create($parameter->name ?? $name, $parameter->required)
                ->setSchema($schema)
            ;
            $collection->add($defParam);
        }

        return $collection;
    }

    private function getRequests(?RequestBody $requestBody): Bodies
    {
        $collection = new Bodies();
        if ($requestBody === null) {
            return $collection;
        }

        foreach ($requestBody->content as $type => $mediaType) {
            if (!$mediaType->schema instanceof Schema) {
                continue;
            }
            $schema = $mediaType->schema;
            $request = Body::create(
                $schema,
                $type,
            );
            $collection->add($request);
        }

        return $collection;
    }

    /**
     * @param \cebe\openapi\spec\Response[] $responses
     */
    private function getResponses(?iterable $responses): Responses
    {
        $collection = new Responses();
        if ($responses === null) {
            return $collection;
        }
        /** @var string $status */
        foreach ($responses as $status => $response) {
            /** @var Header[] $headers */
            $headers = $response->headers;

            if (\count($response->content) === 0) {
                $defResponse = Response::create((int) $status)
                    ->setHeaders($this->getHeaders($headers))
                    ->setDescription((string) $response->description)
                ;
                $collection->add($defResponse);
                continue;
            }

            /**
             * @var string    $type
             * @var MediaType $mediaType
             */
            foreach ($response->content as $type => $mediaType) {
                /** @var Schema|null $schema */
                $schema = $mediaType->schema;
                $defResponse = Response::create((int) $status)
                    ->setMediaType($type)
                    ->setHeaders($this->getHeaders($headers))
                    ->setBody($schema)
                    ->setDescription((string) $response->description)
                ;
                $collection->add($defResponse);
            }
        }

        return $collection;
    }

    /**
     * @param array<string, SecurityScheme> $securitySchemes
     * @param array<string, string[]>       $requirements
     *
     * @throws DefinitionLoadingException
     */
    private function getSecurities(array $securitySchemes, array $requirements = []): Securities
    {
        $collection = [];
        foreach ($securitySchemes as $name => $scheme) {
            if ($scheme->type === 'apiKey') {
                $collection[] = new ApiKeySecurity($name, $scheme->name, $scheme->in);
            }
            if ($scheme->type === 'http') {
                $collection[] = new HttpSecurity($name, $scheme->scheme, $scheme->bearerFormat);
            }
            $supportedScopes = [];
            if ($scheme->type === 'oauth2' && $scheme->flows !== null) {
                $flows = (array) $scheme->flows->getSerializableData();
                $notFoundRequirements = [];
                /**
                 * @var string    $type
                 * @var OAuthFlow $flow
                 */
                foreach ($flows as $type => $flow) {
                    $scopes = $requirements[$name] ?? [];
                    $flowScopes = $flow->scopes;
                    if (is_object($flowScopes)) {
                        $flowScopes = array_keys((array) $flowScopes);
                    }
                    $flowSupportedScopes = array_values(array_intersect($scopes, $flowScopes));
                    $supportedScopes[] = $flowSupportedScopes;
                    if (count($flowSupportedScopes) === 0) {
                        continue;
                    }
                    $flowSupportedScopes = Scopes::fromNames($flowSupportedScopes);
                    $securityName = $name . '_' . $type;
                    if ($type === 'implicit') {
                        $collection[] = new OAuth2ImplicitSecurity(
                            $securityName,
                            $flow->authorizationUrl,
                            $flowSupportedScopes
                        );
                    }
                    if ($type === 'password') {
                        $collection[] = new OAuth2PasswordSecurity(
                            $securityName,
                            $flow->tokenUrl,
                            $flowSupportedScopes
                        );
                    }
                    if ($type === 'clientCredentials') {
                        $collection[] = new OAuth2ClientCredentialsSecurity(
                            $securityName,
                            $flow->tokenUrl,
                            $flowSupportedScopes
                        );
                    }
                    if ($type === 'authorizationCode') {
                        $collection[] = new OAuth2AuthorizationCodeSecurity(
                            $securityName,
                            $flow->authorizationUrl,
                            $flow->tokenUrl,
                            $flowSupportedScopes,
                        );
                    }
                }
                $notFoundRequirements = array_diff(
                    $requirements[$name] ?? [],
                    array_unique(array_merge(...$supportedScopes))
                );
                if ($notFoundRequirements !== []) {
                    $notFoundRequirements = Json::encode($notFoundRequirements);
                    throw new DefinitionLoadingException(
                        "Scopes '{$notFoundRequirements}' not configured in securitySchemes"
                    );
                }
            }
        }

        return new Securities($collection);
    }

    /**
     * @param \cebe\openapi\spec\Parameter[] $parameters
     */
    private function getExamples(\cebe\openapi\spec\Operation $operation, array $parameters): OperationExamples
    {
        $examples = [];

        $successStatusCode = null;
        if ($operation->responses !== null) {
            $successStatusCodes = array_filter(
                array_keys($operation->responses->getResponses()),
                static fn ($status) => in_array($status, [200, 201, 206], true)
            );
            $successStatusCode = array_shift($successStatusCodes);
        }

        $operationExample = null;
        foreach ($parameters as $parameter) {
            foreach ($parameter->examples ?? [] as $name => $example) {
                $operationExample = $this->getExample((string) $name, $examples);
                $operationExample->setParameter(
                    $parameter->name,
                    $example->value,
                    $parameter->in,
                    $parameter->schema instanceof Schema ? $parameter->schema->type : null,
                );
            }
            if ($parameter->example !== null) {
                $operationExample = $this->getExample('default', $examples);
                $operationExample->setParameter(
                    $parameter->name,
                    $parameter->example,
                    $parameter->in,
                    $parameter->schema instanceof Schema ? $parameter->schema->type : null,
                );
            }
            if ($parameter->schema instanceof Schema &&
                ($parameter->schema->example !== null || $parameter->schema->type === 'object')
            ) {
                $deepObject = $parameter->style === 'deepObject';

                $operationExample = $this->getExample('default', $examples, $successStatusCode);
                try {
                    $example = $this->extractDeepExamples($parameter->schema, path: 'parameter.' . $parameter->name);
                } catch (InvalidExampleException $e) {
                    $this->logger->warning($e->getMessage());
                    continue;
                }
                $operationExample->setParameter(
                    $parameter->name,
                    $example,
                    $parameter->in,
                    $parameter->schema->type,
                    $deepObject
                );
            }

            if ($operationExample === null || !$operationExample->hasParameter($parameter->name, $parameter->in)) {
                if ($parameter->schema instanceof Schema && isset($parameter->schema->default)) {
                    $operationExample = $this->getExample('default', $examples);
                    $operationExample->setParameter(
                        $parameter->name,
                        $parameter->schema->default,
                        $parameter->in,
                        $parameter->schema->type,
                    );
                } elseif ($parameter->required) {
                    $this->logger->warning(
                        "Parameter {$parameter->name} is required for operation {$operation->operationId}, but no example found, skipping..."
                    );
                }
            }
        }

        if ($operation->requestBody instanceof RequestBody) {
            foreach ($operation->requestBody->content as $mediaType) {
                /** @var Example $example */
                foreach ($mediaType->examples ?? [] as $name => $example) {
                    $operationExample = $this->getExample($name, $examples);
                    $operationExample->setBody(BodyExample::create((array) $example->value));
                }
                if ($mediaType->example !== null) {
                    $operationExample = $this->getExample('default', $examples);
                    $operationExample->setBody(BodyExample::create((array) $mediaType->example));
                }
                if ($mediaType->schema instanceof Schema) {
                    if ($mediaType->schema->example !== null) {
                        $operationExample = $this->getExample('default', $examples);
                        $operationExample->setBody(BodyExample::create((array) $mediaType->schema->example));
                    } else {
                        try {
                            $example = (array) $this->extractDeepExamples(
                                $mediaType->schema,
                                path: $operation->operationId . '.requestBody.mediaType.schema'
                            );
                        } catch (InvalidExampleException $e) {
                            $this->logger->warning($e->getMessage());
                            continue;
                        }
                        $operationExample = $this->getExample('default', $examples);
                        $operationExample->setBody(BodyExample::create($example));
                    }
                }
            }
            if (isset($operation->requestBody->required)
                && $operation->requestBody->required === false
                && $operationExample?->getBody() === null) {
                $this->logger->warning(
                    "Request body is required for operation {$operation->operationId}, but no example found, skipping..."
                );

                return new OperationExamples([]);
            }
        }

        foreach ($operation->responses ?? [] as $statusCode => $response) {
            if (\count($response->content) === 0) {
                $operationExample = $this->getExample('default', $examples);
                $operationExample->setResponse(new ResponseExample((string) $statusCode));
                break;
            }
            foreach ($response->content as $mediaType) {
                /**
                 * @var string  $name
                 * @var Example $example
                 */
                foreach ($mediaType->examples ?? [] as $name => $example) {
                    $operationExample = $this->getExample($name, $examples);
                    $operationExample->setResponse(
                        ResponseExample::create((string) $statusCode, (array) $example->value)
                    );
                }
                /** @var Example|null $example */
                $example = $mediaType->example;
                if ($example !== null) {
                    $operationExample = $this->getExample('default', $examples);
                    $operationExample->setResponse(new ResponseExample((string) $statusCode, (array) $example->value));
                }
                if ($mediaType->schema instanceof Schema) {
                    if ($mediaType->schema->example !== null) {
                        $operationExample = $this->getExample('default', $examples);
                        $operationExample->setResponse(
                            new ResponseExample((string) $statusCode, (array) $mediaType->schema->example)
                        );
                    } else {
                        try {
                            $example = $this->extractDeepExamples(
                                $mediaType->schema,
                                path: $operation->operationId . '.responseBody.mediaType.schema'
                            );
                        } catch (InvalidExampleException $e) {
                            $this->logger->warning($e->getMessage());
                            continue;
                        }
                        $operationExample = $this->getExample('default', $examples);
                        $operationExample->setResponse(
                            new ResponseExample((string) $statusCode, $example)
                        );
                    }
                }
            }
            break;
        }

        return new OperationExamples($examples);
    }

    /**
     * @param Header[] $headers
     */
    private function getHeaders(array $headers): Parameters
    {
        $collection = new Parameters();
        foreach ($headers as $name => $header) {
            /** @var Schema|null $schema */
            $schema = $header->schema;
            $defHeader = new Parameter(
                $header->name ?? $name,
                $header->required,
                $schema
            );
            $collection->add($defHeader);
        }

        return $collection;
    }

    /**
     * @param array<OperationExample> $examples
     */
    private function getExample(string $name, array &$examples, ?int $statusCode = null): OperationExample
    {
        if (!isset($examples[$name])) {
            $examples[$name] = new OperationExample($name, null, $statusCode);
        }

        return $examples[$name];
    }

    /**
     * naive check for circular references for performance reasons
     */
    private function hasRepeatingConsecutivePattern(string $s): bool
    {
        if (mb_strlen($s) === 0) {
            return false;
        }
        $parts = explode('.', $s);
        $lastPart = $parts[count($parts) - 1];
        $count = mb_substr_count($s, $lastPart);

        return $count > 3;
    }

    /**
     * @throws InvalidExampleException
     */
    private function extractDeepExamples(Schema $schema, bool $optional = false, string $path = ''): mixed
    {
        if ($this->hasRepeatingConsecutivePattern($path)) {
            $this->logger->warning("Found circular reference in path: {$path}, using null as example");

            return null;
        }

        if (isset($schema->type)) {
            if ($schema->type === 'array' && $schema->items instanceof Schema) {
                if ($schema->example !== null) {
                    return $schema->example;
                }

                return [
                    $this->extractDeepExamples($schema->items, false, $path),
                ];
            }

            if ($schema->type === 'object' && isset($schema->properties)) {
                $example = [];
                foreach ($schema->properties as $name => $property) {
                    if (!$property instanceof Schema) {
                        continue;
                    }
                    $isRequired = \in_array($name, $schema->required ?? [], true);
                    try {
                        $example[$name] = $this->extractDeepExamples(
                            $property,
                            !$isRequired,
                            $path . '.' . $name,
                        );
                    } catch (InvalidExampleException $e) {
                        if ($optional) {
                            continue;
                        }
                        throw $e;
                    }
                }

                return $example;
            }
        }

        if (isset($schema->example)) {
            return $schema->example;
        }

        if (isset($schema->default)) {
            return $schema->default;
        }

        if (!empty($schema->nullable)) {
            return null;
        }

        throw new InvalidExampleException(
            'Could not extract example for ' . $path
        );
    }
}
