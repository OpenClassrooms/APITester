<?php

declare(strict_types=1);

namespace APITester\Preparator\Config\PaginationError;

final class RangeConfig
{
    public const HEADER_RANGE = 'header';

    public const QUERY_PARAM_RANGE = 'query';

    /**
     * @param string[] $names
     */
    public function __construct(
        private string $in,
        private array $names,
        private ?string $unit = null
    ) {
        if (!$this->validate()) {
            throw new \InvalidArgumentException('Invalid RangeConfig Error Config');
        }
    }

    public function inHeader(): bool
    {
        return $this->getIn() === self::HEADER_RANGE;
    }

    public function inQuery(): bool
    {
        return $this->getIn() === self::QUERY_PARAM_RANGE;
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
        if (\count($this->names) !== 2) {
            throw new \InvalidArgumentException('Cannot get lower value if config item is not in query.');
        }

        return $this->names[0];
    }

    public function getUpper(): string
    {
        if (\count($this->names) !== 2) {
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
        return ($this->inHeader() && \count($this->names) === 1 && $this->unit !== null)
            || ($this->inQuery() && \count($this->names) === 2);
    }
}
