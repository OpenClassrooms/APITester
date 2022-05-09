<?php

declare(strict_types=1);

namespace APITester\Definition\Example;

use APITester\Util\Json;

final class BodyExample
{
    private OperationExample $parent;

    private string $mediaType = 'application/json';

    /**
     * @var mixed[]
     */
    private array $content;

    /**
     * @param mixed[] $content
     */
    public function __construct(array $content = [])
    {
        $this->content = $content;
    }

    /**
     * @param mixed[] $content
     */
    public static function create(array $content = []): self
    {
        return new self($content);
    }

    public function getStringContent(): string
    {
        if ('application/json' !== $this->mediaType) {
            throw new \RuntimeException('Unsupported get string content for mediaType: ' . $this->mediaType);
        }

        return Json::encode($this->getContent());
    }

    /**
     * @return mixed[]
     */
    public function getContent(): array
    {
        return $this->content;
    }

    public function getParent(): OperationExample
    {
        return $this->parent;
    }

    public function setParent(OperationExample $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): void
    {
        $this->mediaType = $mediaType;
    }
}
