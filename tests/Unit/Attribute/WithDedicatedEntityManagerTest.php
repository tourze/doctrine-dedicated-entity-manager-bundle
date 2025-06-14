<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Unit\Attribute;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;

class WithDedicatedEntityManagerTest extends TestCase
{
    public function testAttributeCreation(): void
    {
        $attribute = new WithDedicatedEntityManager('test_channel');
        
        $this->assertEquals('test_channel', $attribute->channel);
    }

    public function testAttributeIsTargetingClass(): void
    {
        $reflection = new \ReflectionClass(WithDedicatedEntityManager::class);
        $attributes = $reflection->getAttributes(\Attribute::class);
        
        $this->assertCount(1, $attributes);
        
        $attributeInstance = $attributes[0]->newInstance();
        $this->assertEquals(\Attribute::TARGET_CLASS, $attributeInstance->flags);
    }
}