<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

final class Parameter
{
    private ?string $name = null;
    private mixed $value;

    private function __construct(?string $name, mixed $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public static function of(mixed $value): self
    {
        return new self(null, $value);
    }

    public static function named(string $name, mixed $value): self
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

    public function value(): mixed
    {
        return $this->value;
    }
}
