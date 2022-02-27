<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\Query\Parameter\Type;
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class Parameter
{
    /** @var ?non-empty-string */
    private ?string $name = null;
    private mixed $value;
    private Type $type;

    /**
     * @param ?non-empty-string $name
     */
    private function __construct(?string $name, mixed $value, ?Type $type)
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type ?? Type::unspecified;
    }

    /**
     * @psalm-pure
     */
    public static function of(mixed $value, Type $type = null): self
    {
        return new self(null, $value, $type);
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     */
    public static function named(string $name, mixed $value, Type $type = null): self
    {
        return new self($name, $value, $type);
    }

    /**
     * @return Maybe<non-empty-string>
     */
    public function name(): Maybe
    {
        return Maybe::of($this->name);
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
