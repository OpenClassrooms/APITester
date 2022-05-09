<?php

declare(strict_types=1);

namespace APITester\Preparator\Config\PaginationError;

final class RangeConfig
{
    public const HEADER_RANGE = 'header';
    public const QUERY_PARAM_RANGE = 'query';

    private string $in;

    /**
     * @var string[]
     */
    private array $names;

    private ?string $unit;

    /**
     * @param string[] $names
     */
    public function __construct(string $in, array $names, string $unit = null)
    {
        $this->in = $in;
        $this->names = $names;
        $this->unit = $unit;

        if (!$this->validate()) {
            throw new \InvalidArgumentException('Invalid RangeConfig Error Config');
        }
    }

    public function inHeader(): bool
    {
        return self::HEADER_RANGE === $this->getIn();
    }

    public function inQuery(): bool
    {
        return self::QUERY_PARAM_RANGE === $this->getIn();
    }

    public function getIn(): string
    {
        return $this->in;
    }

    public function setIn(string $in): self
    {
        $this->in = $in;

        return $this;
    }

    public function getLower(): string
    {
        if (2 !== \count($this->names)) {
            throw new \InvalidArgumentException('Cannot get lower value if config item is not in query.');
        }

        return $this->names[0];
    }

    public function getUpper(): string
    {
        if (2 !== \count($this->names)) {
            throw new \InvalidArgumentException('Cannot get lower value if config item is not in query.');
        }

        return $this->names[1];
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        return $this->names;
    }

    /**
     * @param string[] $names
     */
    public function setNames(array $names): self
    {
        $this->names = $names;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    private function validate(): bool
    {
        return
            ($this->inHeader() && 1 === \count($this->names) && null !== $this->unit)
            || ($this->inQuery() && 2 === \count($this->names));
    }
}
