<?php
declare(strict_types = 1);

namespace Formal\AccessLayer;

use Formal\AccessLayer\Query\Parameter;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Query
{
    /** @var non-empty-string */
    private string $sql;
    private bool $lazy;
    /** @var Sequence<Parameter> */
    private Sequence $parameters;

    /**
     * @param non-empty-string $sql
     * @param Sequence<Parameter> $parameters
     */
    private function __construct(string $sql, bool $lazy, Sequence $parameters)
    {
        $this->sql = $sql;
        $this->lazy = $lazy;
        $this->parameters = $parameters;
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $sql
     * @param Sequence<Parameter> $parameters
     */
    public static function of(string $sql, ?Sequence $parameters = null): self
    {
        return new self($sql, false, $parameters ?? Sequence::of());
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $sql
     * @param Sequence<Parameter> $parameters
     */
    public static function lazily(string $sql, ?Sequence $parameters = null): self
    {
        return new self($sql, true, $parameters ?? Sequence::of());
    }

    public function with(Parameter $parameter): self
    {
        return new self(
            $this->sql,
            $this->lazy,
            ($this->parameters)($parameter),
        );
    }

    /**
     * @return Sequence<Parameter>
     */
    public function parameters(): Sequence
    {
        return $this->parameters;
    }

    /**
     * @return non-empty-string
     */
    public function sql(): string
    {
        return $this->sql;
    }

    public function lazy(): bool
    {
        return $this->lazy;
    }
}
