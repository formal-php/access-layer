<?php
declare(strict_types = 1);

namespace Tests\Formal\AccessLayer\Query\Parameter;

use Formal\AccessLayer\Query\Parameter\Type;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class TypeTest extends TestCase
{
    use BlackBox;

    public function testTheSameInstanceIsAlwaysReturned()
    {
        $this
            ->forAll($this->elements())
            ->then(function($type) {
                $this->assertSame(Type::{$type}(), Type::{$type}());
            });
    }

    public function testDifferentInstancesAreReturnedForDifferenceTypes()
    {
        $this
            ->forAll($this->elements(), $this->elements())
            ->filter(static fn($a, $b) => $a !== $b)
            ->then(function($a, $b) {
                $this->assertNotSame(Type::{$a}(), Type::{$b}());
            });
    }

    public function testEquals()
    {
        $this
            ->forAll($this->elements(), $this->elements())
            ->filter(static fn($a, $b) => $a !== $b)
            ->then(function($a, $b) {
                $this->assertTrue(Type::{$a}()->equals(Type::{$a}()));
                $this->assertFalse(Type::{$a}()->equals(Type::{$b}()));
            });
    }

    private function elements(): Set
    {
        return Set\Elements::of('bool', 'null', 'int', 'string', 'unspecified');
    }
}
