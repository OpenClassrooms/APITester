<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Loader;

use cebe\openapi\Reader;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Collection\Headers;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\Parameters;
use OpenAPITesting\Definition\Collection\Requests;
use OpenAPITesting\Definition\Collection\Responses;
use OpenAPITesting\Definition\Collection\SecuritySchemes;
use OpenAPITesting\Definition\Collection\Servers;
use OpenAPITesting\Definition\Collection\Tags;
use OpenAPITesting\Definition\Header;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoadingException;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\Request;
use OpenAPITesting\Definition\Response;
use OpenAPITesting\Definition\SecurityScheme;
use OpenAPITesting\Definition\Server;
use OpenAPITesting\Definition\Tag;

final class OpenApiDefinitionLoader implements DefinitionLoader
{
    public const FORMAT_JSON = 'json';

    public const FORMAT_YAML = 'yaml';

    public const FORMATS = [self::FORMAT_JSON, self::FORMAT_YAML];

    /**
     * @throws DefinitionLoadingException
     * @return Api
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
            throw new DefinitionLoadingException($e);
        }

        $collection = [];
        foreach ($openApi->paths as $path => $pathInfo) {
            foreach ($pathInfo->getOperations() as $method => $operation) {
                /** @var \cebe\openapi\spec\Parameter[] $parameters */
                $parameters = $operation->parameters;
                /** @var RequestBody $requestBody */
                $requestBody = $operation->requestBody;
                /** @var \cebe\openapi\spec\Responses|null $responses */
                $responses = $operation->responses;

                $api->addOperation(
                    Operation::create(
                        $operation->operationId,
                        $path
                    )
                        ->setMethod($method)
                        ->setSummary($operation->summary ?? '')
                        ->setDescription($operation->description ?? '')
                        ->setParameters($this->getParameters($parameters))
                        ->setRequests($this->getRequests($requestBody))
                        ->setResponses($this->getResponses($responses))
                        ->setTags($this->getTags($operation->tags))
                );
            }
        }

        /** @var null|\cebe\openapi\spec\SecurityScheme[] $securitySchemes */
        $securitySchemes = $openApi->components !== null ? $openApi->components->securitySchemes : null;

        return $api
            ->setOperations(new Operations($collection))
            ->setServers($this->getServers($openApi->servers))
            ->setTags($this->getTags($openApi->tags))
            ->setSecuritySchemes($this->getSecuritySchemes($securitySchemes))
        ;
    }

    public static function getFormat(): string
    {
        return 'openapi';
    }

    /**
     * @param \cebe\openapi\spec\Parameter[] $parameters
     */
    private function getParameters(array $parameters): Parameters
    {
        $collection = [];
        foreach ($parameters as $parameter) {
            /** @var Schema|null $schema */
            $schema = $parameter->schema;
            $collection[] = new Parameter(
                $parameter->name,
                $parameter->in,
                $schema
            );
        }

        return new Parameters($collection);
    }

    private function getRequests(?RequestBody $requestBody): Requests
    {
        if (null === $requestBody) {
            return new Requests();
        }

        $requests = [];
        foreach ($requestBody->content as $type => $mediaType) {
            /** @var Schema|null $schema */
            $schema = $mediaType->schema;
            $requests[$type] = new Request(
                $schema,
                $type,
                $requestBody->required
            );
        }

        return new Requests($requests);
    }

    private function getResponses(?\cebe\openapi\spec\Responses $responses): Responses
    {
        if (null === $responses) {
            return new Responses();
        }
        $collection = [];
        /** @var string $status */
        foreach ($responses as $status => $response) {
            /**
             * @var string $type
             * @var MediaType $mediaType
             */
            foreach ($response->content as $type => $mediaType) {
                /** @var Schema|null $schema */
                $schema = $mediaType->schema;
                /** @var \cebe\openapi\spec\Header[] $headers */
                $headers = $response->headers;
                $collection[$status . '_' . $type] = new Response(
                    $type,
                    (int) $status,
                    $this->getHeaders($headers),
                    $schema,
                    $response->description,
                );
            }
        }

        return new Responses($collection);
    }

    /**
     * @param \cebe\openapi\spec\Header[] $headers
     */
    private function getHeaders(array $headers): Headers
    {
        $collection = [];
        foreach ($headers as $header) {
            /** @var Schema|null $schema */
            $schema = $header->schema;
            $collection[] = new Header(
                $header->name,
                $schema
            );
        }

        return new Headers($collection);
    }

    /**
     * @param \cebe\openapi\spec\Tag[]|string[] $tags
     */
    private function getTags(array $tags): Tags
    {
        $collection = [];

        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                $tag = $tag->name;
            }
            $collection[] = new Tag($tag);
        }

        return new Tags($collection);
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
     * @param null|\cebe\openapi\spec\SecurityScheme[] $securitySchemes
     */
    private function getSecuritySchemes(?array $securitySchemes): SecuritySchemes
    {
        if (null === $securitySchemes) {
            return new SecuritySchemes();
        }

        $collection = [];
        foreach ($securitySchemes as $scheme) {
            $collection[] = new SecurityScheme($scheme->type, $scheme->flows);
        }

        return new SecuritySchemes($collection);
    }
}
