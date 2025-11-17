<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Connection,
    Query,
};
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class Lazy implements Implementation
{
    /** @var callable(): Connection */
    private $load;
    private ?Connection $connection = null;

    /**
     * @param callable(): Connection $load
     */
    private function __construct(callable $load)
    {
        $this->load = $load;
    }

    #[\Override]
    public function __invoke(Query $query): Sequence
    {
        return ($this->connection())($query);
    }

    /**
     * @param callable(): Connection $load
     */
    public static function of(callable $load): self
    {
        return new self($load);
    }

    private function connection(): Connection
    {
        return $this->connection ?? $this->connection = ($this->load)();
    }
}
