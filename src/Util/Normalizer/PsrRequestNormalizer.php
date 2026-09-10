<?php

declare(strict_types=1);

namespace APITester\Util\Normalizer;

use APITester\Util\Json;
use Nyholm\Psr7\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PsrRequestNormalizer implements NormalizerInterface
{
    /**
     * @param array<mixed, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Request;
    }

    /**
     * @param array<mixed, mixed> $context
     *
     * @return array{'method': string,
     *              'url': string,
     *              'body': string|array<mixed>,
     *              'headers': array<string, string>
     * }
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        /** @var Request $data */
        $result = [
            'method' => $data->getMethod(),
            'url' => (string) $data->getUri(),
            'body' => Json::isJson((string) $data->getBody()) ? Json::decode(
                (string) $data->getBody()
            ) : (string) $data->getBody(),
            'headers' => $data->getHeaders(),
        ];

        if (isset($context[AbstractNormalizer::IGNORED_ATTRIBUTES])) {
            /** @var string[] $attrs */
            $attrs = $context[AbstractNormalizer::IGNORED_ATTRIBUTES];
            $result = array_diff_key($result, array_flip($attrs));
        }

        /**
         * @var array{'method': string,
         *          'url': string,
         *          'body': string|array<mixed>,
         *          'headers': array<string, string>
         * }
         */
        return $result;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Request::class => true,
        ];
    }
}
