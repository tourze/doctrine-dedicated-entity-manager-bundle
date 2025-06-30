<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineDedicatedEntityManagerBundle\Exception\InvalidArgumentException;

class InvalidArgumentExceptionTest extends TestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new InvalidArgumentException('Test message');
        
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionWithCode(): void
    {
        $exception = new InvalidArgumentException('Test message', 456);
        
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(456, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new InvalidArgumentException('Test message', 0, $previous);
        
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testEmptyMessage(): void
    {
        $exception = new InvalidArgumentException('');
        
        $this->assertEquals('', $exception->getMessage());
    }

    public function testStaticUsage(): void
    {
        $exception = new InvalidArgumentException('Argument is invalid');
        
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertEquals('Argument is invalid', $exception->getMessage());
    }
}