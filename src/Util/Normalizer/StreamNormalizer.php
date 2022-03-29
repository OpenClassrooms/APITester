<?php

declare(strict_types=1);

namespace APITester\Util\Normalizer;

use Psr\Http\Message\StreamInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

final class StreamNormalizer implements ContextAwareNormalizerInterface
{
    /**
     * @param mixed $data
     * @param mixed $format
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof StreamInterface;
    }

    /**
     * @param mixed $object
     * @param mixed $format
     */
    public function normalize($object, $format = null, array $context = []): string
    {
        return (string) $object;
    }
}
