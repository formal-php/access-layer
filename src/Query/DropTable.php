<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Query;

use Formal\AccessLayer\{
    Query,
    Table\Name,
    Driver,
};

/**
 * @psalm-immutable
 */
final class DropTable implements Builder
{
    private Name $name;
    private bool $ifExists;

    private function __construct(bool $ifExists, Name $name)
    {
        $this->ifExists = $ifExists;
        $this->name = $name;
    }

    /**
     * @psalm-pure
     */
    public static function named(Name $name): self
    {
        return new self(false, $name);
    }

    /**
     * @psalm-pure
     */
    public static function ifExists(Name $name): self
    {
        return new self(true, $name);
    }

    #[\Override]
    public function normalize(Driver $driver): Query
    {
        return SQL::of(\sprintf(
            'DROP TABLE %s %s',
            $this->ifExists ? 'IF EXISTS' : '',
            $this->name->sql($driver),
        ));
    }
}
