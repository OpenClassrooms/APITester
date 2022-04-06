<?php

declare(strict_types=1);

namespace APITester\Util\Normalizer;

use APITester\Util\Json;
use Nyholm\Psr7\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

final class PsrRequestNormalizer implements ContextAwareNormalizerInterface
{
    /**
     * @inerhitDoc
     *
     * @param mixed      $data
     * @param mixed|null $format
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof Request;
    }

    /**
     * @inerhitDoc
     *
     * @param mixed      $object
     * @param mixed|null $format
     *
     * @return array{'method': string,
     *              'url': string,
     *              'body': string|array<mixed>,
     *              'headers': array<string, string>
     * }
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        /** @var Request $object */
        $result = [
            'method' => $object->getMethod(),
            'url' => (string) $object->getUri(),
            'body' => Json::isJson((string) $object->getBody()) ? Json::decode(
                (string) $object->getBody()
            ) : (string) $object->getBody(),
            'headers' => $object->getHeaders(),
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
}
