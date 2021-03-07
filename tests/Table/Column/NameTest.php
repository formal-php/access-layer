<?php
declare(strict_types = 1);

namespace Tests\Formal\AccessLayer\Table\Column;

use Formal\AccessLayer\{
    Table\Column\Name,
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
