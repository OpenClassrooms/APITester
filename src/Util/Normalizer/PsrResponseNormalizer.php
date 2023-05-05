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
     * @param mixed      $data
     * @param mixed|null $format
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Response;
    }

    /**
     * @inerhitDoc
     *
     * @param mixed      $object
     * @param mixed|null $format
     *
     * @return array{
     *          'body': string|array<mixed>,
     *          'status': int,
     *          'headers': array<string, string>
     * }
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        /** @var Response $object */
        $result = [
            'body' => Json::isJson((string) $object->getBody()) ? Json::decode(
                (string) $object->getBody()
            ) : (string) $object->getBody(),
            'headers' => $object->getHeaders(),
            'status' => $object->getStatusCode(),
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
}