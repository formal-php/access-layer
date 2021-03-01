<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Row,
    Table\Name,
};
use Innmind\Immutable\Sequence;

final class DropTable implements Query
{
    private Name $name;
    private bool $ifExists = false;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    public static function ifExists(Name $name): self
    {
        $self = new self($name);
        $self->ifExists = true;

        return $self;
    }

    public function parameters(): Sequence
    {
        return Sequence::of(Row::class);
    }

    public function sql(): string
    {
        return \sprintf(
            'DROP TABLE %s %s',
            $this->ifExists ? 'IF EXISTS' : '',
            $this->name->sql(),
        );
    }
}
