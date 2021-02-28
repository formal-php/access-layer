<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

final class Parameter
{
    private ?string $name = null;
    /** @var mixed */
    private $value;

    /**
     * @param mixed $value
     */
    private function __construct(?string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @param mixed $value
     */
    public static function of($value): self
    {
        return new self(null, $value);
    }

    /**
     * @param mixed $value
     */
    public static function named(string $name, $value): self
    {
        return new self($name, $value);
    }

    public function boundByName(): bool
    {
        return \is_string($this->name);
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function name(): string
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }
}
