<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineDedicatedEntityManagerBundle\Exception\ConfigurationException;

class ConfigurationExceptionTest extends TestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new ConfigurationException('Test message');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionWithCode(): void
    {
        $exception = new ConfigurationException('Test message', 123);
        
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(123, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new ConfigurationException('Test message', 0, $previous);
        
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testEmptyMessage(): void
    {
        $exception = new ConfigurationException('');
        
        $this->assertEquals('', $exception->getMessage());
    }
}