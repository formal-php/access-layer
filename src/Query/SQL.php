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
    private string $sql;
    /** @var Sequence<Parameter> */
    private Sequence $parameters;

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
