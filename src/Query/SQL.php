<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Driver,
};
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
     */
    public static function of(string $sql): self
    {
        return new self($sql, false, Sequence::of());
    }

    /**
     * @psalm-pure
     * @deprecated Use ::lazily() instead
     *
     * @param non-empty-string $sql
     */
    public static function onDemand(string $sql): self
    {
        return self::lazily($sql);
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $sql
     */
    public static function lazily(string $sql): self
    {
        return new self($sql, true, Sequence::of());
    }

    public function with(Parameter $parameter): self
    {
        return new self(
            $this->sql,
            $this->lazy,
            ($this->parameters)($parameter),
        );
    }

    #[\Override]
    public function parameters(): Sequence
    {
        return $this->parameters;
    }

    #[\Override]
    public function sql(Driver $driver): string
    {
        return $this->sql;
    }

    #[\Override]
    public function lazy(): bool
    {
        return $this->lazy;
    }
}
