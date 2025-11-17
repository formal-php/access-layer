<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\Query;
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class Lazy implements Implementation
{
    /** @var callable(): Implementation */
    private $load;
    private ?Implementation $connection = null;

    /**
     * @param callable(): Implementation $load
     */
    private function __construct(callable $load)
    {
        $this->load = $load;
    }

    #[\Override]
    public function __invoke(Query|Query\Builder $query): Sequence
    {
        return ($this->connection())($query);
    }

    /**
     * @param callable(): Implementation $load
     */
    public static function of(callable $load): self
    {
        return new self($load);
    }

    private function connection(): Implementation
    {
        return $this->connection ?? $this->connection = ($this->load)();
    }
}
