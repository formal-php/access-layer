<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query\Constraint;

use Formal\AccessLayer\Table\{
    Name,
    Column,
};
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class ForeignKey
{
    private Column\Name $column;
    private Name $target;
    private Column\Name $reference;
    /** @var Maybe<non-empty-string> */
    private Maybe $onDelete;
    /** @var Maybe<non-empty-string> */
    private Maybe $name;

    /**
     * @param Maybe<non-empty-string> $onDelete
     * @param Maybe<non-empty-string> $name
     */
    private function __construct(
        Column\Name $column,
        Name $target,
        Column\Name $reference,
        Maybe $onDelete,
        Maybe $name,
    ) {
        $this->column = $column;
        $this->target = $target;
        $this->reference = $reference;
        $this->onDelete = $onDelete;
        $this->name = $onDelete;
        $this->name = $name;
    }

    /**
     * @psalm-pure
     */
    public static function of(
        Column\Name $column,
        Name $target,
        Column\Name $reference,
    ): self {
        /** @var Maybe<non-empty-string> */
        $onDelete = Maybe::nothing();
        /** @var Maybe<non-empty-string> */
        $name = Maybe::nothing();

        return new self($column, $target, $reference, $onDelete, $name);
    }

    public function onDeleteCascade(): self
    {
        return new self(
            $this->column,
            $this->target,
            $this->reference,
            Maybe::just('CASCADE'),
            $this->name,
        );
    }

    public function onDeleteSetNull(): self
    {
        return new self(
            $this->column,
            $this->target,
            $this->reference,
            Maybe::just('SET NULL'),
            $this->name,
        );
    }

    /**
     * @param non-empty-string $name
     */
    public function named(string $name): self
    {
        return new self(
            $this->column,
            $this->target,
            $this->reference,
            $this->onDelete,
            Maybe::just($name),
        );
    }

    /**
     * @return non-empty-string
     */
    public function sql(): string
    {
        $sql = \sprintf(
            'CONSTRAINT `FK_%s` FOREIGN KEY (%s) REFERENCES %s(%s)',
            $this->name->match(
                static fn($name) => $name,
                fn() => \sprintf(
                    '%s_%s',
                    $this->column->toString(),
                    $this->reference->toString(),
                ),
            ),
            $this->column->sql(),
            $this->target->sql(),
            $this->reference->sql(),
        );

        return $sql.$this->onDelete->match(
            static fn($strategy) => " ON DELETE $strategy",
            static fn() => '',
        );
    }
}
