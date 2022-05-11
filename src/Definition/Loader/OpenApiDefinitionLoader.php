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

final class OpenApiDefinitionLoader implements DefinitionLoader
{
    public const FORMAT_JSON = 'json';

    public const FORMAT_YAML = 'yaml';

    public const FORMATS = [self::FORMAT_JSON, self::FORMAT_YAML];

    /**
     * @throws DefinitionLoadingException
     */
    public function load(string $filePath, string $format = self::FORMAT_YAML): Api
    {
        $api = Api::create();
        if (!\in_array($format, self::FORMATS, true)) {
            throw new \InvalidArgumentException('Invalid format ' . $format);
        }
        try {
            /** @var OpenApi $openApi */
            $openApi = Reader::readFromYamlFile($filePath);
        } catch (\Exception $e) {
            throw new DefinitionLoadingException("Could not load {$filePath}", $e);
        }

        /** @var array<string, SecurityScheme> $securitySchemes */
        $securitySchemes = null !== $openApi->components ? $openApi->components->securitySchemes : [];

        return $api
            ->setOperations($this->getOperations($openApi->paths->getPaths(), $securitySchemes))
            ->setServers($this->getServers($openApi->servers))
            ->setTags($this->getTags($openApi->tags))
        ;
    }

    public static function getFormat(): string
    {
        return 'openapi';
    }

    /**
     * @param array<string, SecurityScheme> $securitySchemes
     * @param array<string, PathItem>       $paths
     *
     * @throws DefinitionLoadingException
     */
    private function getOperations(array $paths, array $securitySchemes): Operations
    {
        $operations = new Operations();
        foreach ($paths as $path => $pathInfo) {
            foreach ($pathInfo->getOperations() as $method => $operation) {
                /** @var \cebe\openapi\spec\Parameter[] $parameters */
                $parameters = array_merge($operation->parameters ?? [], $pathInfo->parameters ?? []);
                /** @var RequestBody $requestBody */
                $requestBody = $operation->requestBody;
                /** @var \cebe\openapi\spec\Response[]|null $responses */
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
            $defParam = Parameter::create($parameter->name ?? $name)
                ->setSchema($schema)
            ;
            $collection->add($defParam);
        }

        return $collection;
    }

    private function getRequests(?RequestBody $requestBody): Bodies
    {
        $collection = new Bodies();
        if (null === $requestBody) {
            return $collection;
        }

        foreach ($requestBody->content as $type => $mediaType) {
            if (!$mediaType->schema instanceof Schema) {
                continue;
            }
            $schema = $mediaType->schema;
            $request = Body::create(
                $type,
                $schema,
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
        if (null === $responses) {
            return $collection;
        }
        /** @var string $status */
        foreach ($responses as $status => $response) {
            /** @var Header[] $headers */
            $headers = $response->headers;

            if (0 === \count($response->content)) {
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
            if ('apiKey' === $scheme->type) {
                $collection[] = new ApiKeySecurity($name, $scheme->name, $scheme->in);
            }
            if ('http' === $scheme->type) {
                $collection[] = new HttpSecurity($name, $scheme->scheme, $scheme->bearerFormat);
            }
            if ('oauth2' === $scheme->type && null !== $scheme->flows) {
                $notFoundRequirements = [];
                /**
                 * @var string    $type
                 * @var OAuthFlow $flow
                 */
                foreach ((array) $scheme->flows->getSerializableData() as $type => $flow) {
                    $scopes = $requirements[$name] ?? [];
                    /** @var object $flowScopes */
                    $flowScopes = $flow->scopes;
                    $diff = array_diff($scopes, array_keys((array) $flowScopes));
                    if (\count($diff) > 0) {
                        $notFoundRequirements = $diff;
                        continue;
                    }
                    $notFoundRequirements = [];
                    $scopes = Scopes::fromNames($scopes);
                    $name .= '_' . $type;
                    if ('implicit' === $type) {
                        $collection[] = new OAuth2ImplicitSecurity(
                            $name,
                            $flow->authorizationUrl,
                            $scopes
                        );
                    }
                    if ('password' === $type) {
                        $collection[] = new OAuth2PasswordSecurity(
                            $name,
                            $flow->tokenUrl,
                            $scopes
                        );
                    }
                    if ('clientCredentials' === $type) {
                        $collection[] = new OAuth2ClientCredentialsSecurity(
                            $name,
                            $flow->tokenUrl,
                            $scopes
                        );
                    }
                    if ('authorizationCode' === $type) {
                        $collection[] = new OAuth2AuthorizationCodeSecurity(
                            $name,
                            $flow->authorizationUrl,
                            $flow->tokenUrl,
                            $scopes,
                        );
                    }
                }
                if (\count($notFoundRequirements) > 0) {
                    $notFoundRequirements = implode(',', $notFoundRequirements);
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

        foreach ($parameters as $parameter) {
            foreach ($parameter->examples ?? [] as $name => $example) {
                $operationExample = $this->getExample((string) $name, $examples);
                $operationExample->setParameter($parameter->name, (string) $example->value, $parameter->in);
            }
            if (null !== $parameter->example) {
                $operationExample = $this->getExample('default', $examples);
                $operationExample->setParameter($parameter->name, (string) $parameter->example, $parameter->in);
            }
            if ($parameter->schema instanceof Schema && null !== $parameter->schema->example) {
                $example = $parameter->schema->example;
                if (\is_array($example)) {
                    $example = implode(',', $example);
                }
                $operationExample = $this->getExample('properties', $examples);
                $operationExample->setParameter($parameter->name, (string) $example, $parameter->in);
            }
        }

        if ($operation->requestBody instanceof RequestBody) {
            foreach ($operation->requestBody->content as $mediaType) {
                /** @var Example $example */
                foreach ($mediaType->examples ?? [] as $name => $example) {
                    $operationExample = $this->getExample($name, $examples);
                    $operationExample->setBody(BodyExample::create((array) $example->value));
                }
                if (null !== $mediaType->example) {
                    $operationExample = $this->getExample('default', $examples);
                    $operationExample->setBody(BodyExample::create((array) $mediaType->example));
                }
                if ($mediaType->schema instanceof Schema) {
                    if (null !== $mediaType->schema->example) {
                        $operationExample = $this->getExample('default', $examples);
                        $operationExample->setBody(BodyExample::create((array) $mediaType->schema->example));
                    }
                    try {
                        $example = $this->extractDeepExamples($mediaType->schema);
                        $operationExample = $this->getExample('properties', $examples);
                        $operationExample->setBody(BodyExample::create($example));
                    } catch (ExampleNotExtractableException $e) {
                        // @ignoreException
                    }
                }
            }
        }

        foreach ($operation->responses ?? [] as $statusCode => $response) {
            foreach ($response->content as $mediaType) {
                /**
                 * @var string  $name
                 * @var Example $example
                 */
                foreach ($mediaType->examples ?? [] as $name => $example) {
                    $operationExample = $this->getExample($name, $examples);
                    $operationExample->setResponse(ResponseExample::create((array) $example->value, (int) $statusCode));
                }
                /** @var Example|null $example */
                $example = $mediaType->example;
                if (null !== $example) {
                    $operationExample = $this->getExample('default', $examples);
                    $operationExample->setResponse(new ResponseExample((array) $example->value, (int) $statusCode));
                }
                if ($mediaType->schema instanceof Schema) {
                    if (null !== $mediaType->schema->example) {
                        $operationExample = $this->getExample('default', $examples);
                        $operationExample->setResponse(
                            new ResponseExample((array) $mediaType->schema->example, (int) $statusCode)
                        );
                    }
                    try {
                        $example = $this->extractDeepExamples($mediaType->schema);
                        $operationExample = $this->getExample('properties', $examples);
                        $operationExample->setResponse(
                            new ResponseExample($example, (int) $statusCode)
                        );
                    } catch (ExampleNotExtractableException $e) {
                        // @ignoreException
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
    private function getExample(string $name, array &$examples): OperationExample
    {
        if (!isset($examples[$name])) {
            $examples[$name] = new OperationExample($name);
        }

        return $examples[$name];
    }

    /**
     * @throws ExampleNotExtractableException
     *
     * @return array<mixed>
     */
    private function extractDeepExamples(Schema $schema, bool $optional = false): array
    {
        $parent = [];
        if ('object' === $schema->type) {
            foreach ($schema->properties as $name => $property) {
                if (!$property instanceof Schema) {
                    continue;
                }
                if (isset($property->type) && 'object' === $property->type && !isset($property->example)) {
                    $isRequired = \in_array($name, $property->required ?? [], true);
                    $return = $this->extractDeepExamples(
                        $property,
                        !$isRequired
                    );
                    if ([] !== $return || $isRequired) {
                        $parent[$name] = $return;
                    }
                } else {
                    if (
                        !$optional
                        && !isset($property->default)
                        && !$property->nullable
                        && !isset($property->example)
                        && \in_array($name, $schema->required ?? [], true)) {
                        throw new ExampleNotExtractableException();
                    }
                    if (isset($property->example)) {
                        $parent[$name] = $property->example;
                    } elseif (isset($property->default)) {
                        $parent[$name] = $property->default;
                    } elseif ($property->nullable) {
                        $parent[$name] = null;
                    }
                }
            }
        }

        return $parent;
    }
}
