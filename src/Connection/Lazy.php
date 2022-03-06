<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Connection;

use Formal\AccessLayer\{
    Connection,
    Query,
};
use Innmind\Url\Url;
use Innmind\Immutable\Sequence;

final class Lazy implements Connection
{
    private Url $url;
    private ?Connection $connection = null;

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    public function __invoke(Query $query): Sequence
    {
        return ($this->connection())($query);
    }

    private function connection(): Connection
    {
        return $this->connection ?? $this->connection = PDO::of($this->url);
    }
}
