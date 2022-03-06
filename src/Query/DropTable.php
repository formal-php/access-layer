<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Row,
    Table\Name,
};
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class DropTable implements Query
{
    private Name $name;
    private bool $ifExists = false;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    /**
     * @psalm-pure
     */
    public static function ifExists(Name $name): self
    {
        $self = new self($name);
        $self->ifExists = true;

        return $self;
    }

    public function parameters(): Sequence
    {
        /** @var Sequence<Query\Parameter> */
        return Sequence::of();
    }

    public function sql(): string
    {
        /** @var non-empty-string */
        return \sprintf(
            'DROP TABLE %s %s',
            $this->ifExists ? 'IF EXISTS' : '',
            $this->name->sql(),
        );
    }

    public function lazy(): bool
    {
        return false;
    }
}
