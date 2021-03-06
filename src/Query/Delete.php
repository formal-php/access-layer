<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Query\Parameter,
    Query\Parameter\Type,
    Table\Name,
    Table\Column,
    Row,
};
use Innmind\Immutable\Sequence;

final class Delete implements Query
{
    private Name $table;

    public function __construct(Name $table)
    {
        $this->table = $table;
    }

    public function parameters(): Sequence
    {
        return Sequence::of(Parameter::class);
    }

    public function sql(): string
    {
        return \sprintf(
            'DELETE FROM %s',
            $this->table->sql(),
        );
    }
}
