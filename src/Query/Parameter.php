<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\Query\Parameter\Type;

final class Parameter
{
    private ?string $name = null;
    private mixed $value;
    private Type $type;

    private function __construct(?string $name, mixed $value, ?Type $type)
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type ?? Type::unspecified;
    }

    public static function of(mixed $value, Type $type = null): self
    {
        return new self(null, $value, $type);
    }

    public static function named(string $name, mixed $value, Type $type = null): self
    {
        return new self($name, $value, $type);
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

    public function type(): Type
    {
        return $this->type;
    }
}
