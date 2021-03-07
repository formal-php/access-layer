<?php
declare(strict_types = 1);

namespace Tests\Formal\AccessLayer\Table;

use Formal\AccessLayer\{
    Table\Name,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class NameTest extends TestCase
{
    public function testANameCantBeEmpty()
    {
        $this->expectException(DomainException::class);

        new Name('');
    }
}
