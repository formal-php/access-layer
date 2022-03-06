<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\Query;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class SQL implements Query
{
    /** @var non-empty-string */
    private string $sql;
    private bool $lazy;
    /** @var Sequence<Parameter> */
    private Sequence $parameters;

    /**
     * @param non-empty-string $sql
     */
    private function __construct(string $sql, bool $lazy)
    {
        $this->sql = $sql;
        $this->lazy = $lazy;
        /** @var Sequence<Parameter> */
        $this->parameters = Sequence::of();
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $sql
     */
    public static function of(string $sql): self
    {
        return new self($sql, false);
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $sql
     */
    public static function onDemand(string $sql): self
    {
        return new self($sql, true);
    }

    public function with(Parameter $parameter): self
    {
        $self = clone $this;
        $self->parameters = ($this->parameters)($parameter);

        return $self;
    }

    public function parameters(): Sequence
    {
        return $this->parameters;
    }

    public function sql(): string
    {
        return $this->sql;
    }

    public function lazy(): bool
    {
        return $this->lazy;
    }
}
