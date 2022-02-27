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
    /** @var Sequence<Parameter> */
    private Sequence $parameters;

    /**
     * @param non-empty-string $sql
     */
    public function __construct(string $sql)
    {
        $this->sql = $sql;
        /** @var Sequence<Parameter> */
        $this->parameters = Sequence::of();
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
}
