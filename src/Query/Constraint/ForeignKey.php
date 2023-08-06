<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query\Constraint;

use Formal\AccessLayer\Table\{
    Name,
    Column,
};

/**
 * @psalm-immutable
 */
final class ForeignKey
{
    private Column\Name $column;
    private Name $target;
    private Column\Name $reference;

    private function __construct(
        Column\Name $column,
        Name $target,
        Column\Name $reference,
    ) {
        $this->column = $column;
        $this->target = $target;
        $this->reference = $reference;
    }

    /**
     * @psalm-pure
     */
    public static function of(
        Column\Name $column,
        Name $target,
        Column\Name $reference,
    ): self {
        return new self($column, $target, $reference);
    }

    /**
     * @return non-empty-string
     */
    public function sql(): string
    {
        return \sprintf(
            'CONSTRAINT `FK_%s_%s` FOREIGN KEY (%s) REFERENCES %s(%s)',
            $this->column->toString(),
            $this->reference->toString(),
            $this->column->sql(),
            $this->target->sql(),
            $this->reference->sql(),
        );
    }
}
