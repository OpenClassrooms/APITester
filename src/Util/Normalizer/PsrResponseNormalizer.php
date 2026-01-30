<?php

declare(strict_types=1);

namespace APITester\Util\Normalizer;

use APITester\Util\Json;
use Nyholm\Psr7\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PsrResponseNormalizer implements NormalizerInterface
{
    /**
     * @inerhitDoc
     *
     * @param mixed|null $format
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Response;
    }

    /**
     * @inerhitDoc
     *
     * @param mixed|null $format
     *
     * @return array{
     *          'body': string|array<mixed>,
     *          'status': int,
     *          'headers': array<string, string>
     * }
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        /** @var Response $data */
        $result = [
            'body' => Json::isJson((string) $data->getBody()) ? Json::decode(
                (string) $data->getBody()
            ) : (string) $data->getBody(),
            'headers' => $data->getHeaders(),
            'status' => $data->getStatusCode(),
        ];

        if (isset($context[AbstractNormalizer::IGNORED_ATTRIBUTES])) {
            /** @var string[] $attrs */
            $attrs = $context[AbstractNormalizer::IGNORED_ATTRIBUTES];
            $result = array_diff_key($result, array_flip($attrs));
        }

        /**
         * @var array{
         *          'body': string|array<mixed>,
         *          'status': int,
         *          'headers': array<string, string>
         * }
         */
        return $result;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Response::class => true,
        ];
    }
}
