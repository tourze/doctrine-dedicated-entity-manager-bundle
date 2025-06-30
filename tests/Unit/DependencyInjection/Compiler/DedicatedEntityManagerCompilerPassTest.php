<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;
use Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection\Compiler\DedicatedEntityManagerCompilerPass;

class DedicatedEntityManagerCompilerPassTest extends TestCase
{
    private DedicatedEntityManagerCompilerPass $compilerPass;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->compilerPass = new DedicatedEntityManagerCompilerPass();
        $this->container = new ContainerBuilder();
    }
    
    private function setupMockServices(): void
    {
        // 模拟 doctrine-dedicated-connection-bundle 的工厂服务
        $connectionFactory = new Definition(\stdClass::class);
        $this->container->setDefinition('doctrine_dedicated_connection.factory', $connectionFactory);
        
        // 模拟 doctrine-dedicated-entity-manager-bundle 的工厂服务
        $emFactory = new Definition(\stdClass::class);
        $this->container->setDefinition('doctrine_dedicated_entity_manager.factory', $emFactory);
        
        // 模拟 dedicated-manager-registry 的工厂服务
        $registryFactory = new Definition(\stdClass::class);
        $this->container->setDefinition('doctrine_dedicated_manager_registry.factory', $registryFactory);
    }

    public function testProcessAttributedServices(): void
    {
        // 模拟必要的依赖服务
        $this->setupMockServices();
        
        // 创建一个带有属性的类定义
        $definition = new Definition(TestServiceWithAttribute::class);
        $this->container->setDefinition('test_service', $definition);

        // 处理编译器传递
        $this->compilerPass->process($this->container);

        // 验证标签是否已添加
        $this->assertTrue($definition->hasTag('doctrine.dedicated_entity_manager'));
        $tags = $definition->getTag('doctrine.dedicated_entity_manager');
        $this->assertCount(1, $tags);
        $this->assertEquals('test_channel', $tags[0]['channel']);
    }

    public function testProcessTaggedServices(): void
    {
        // 模拟必要的依赖服务
        $this->setupMockServices();
        
        // 创建一个手动标记的服务定义
        $definition = new Definition(\stdClass::class);
        $definition->addTag('doctrine.dedicated_entity_manager', ['channel' => 'manual_channel']);
        $this->container->setDefinition('manual_service', $definition);

        // 处理编译器传递
        $this->compilerPass->process($this->container);

        // 验证服务定义仍然存在
        $this->assertTrue($this->container->hasDefinition('manual_service'));
    }

    public function testProcessWithoutAttributes(): void
    {
        // 创建一个没有属性的类定义
        $definition = new Definition(\stdClass::class);
        $this->container->setDefinition('normal_service', $definition);

        // 处理编译器传递
        $this->compilerPass->process($this->container);

        // 验证没有添加标签
        $this->assertFalse($definition->hasTag('doctrine.dedicated_entity_manager'));
    }

    public function testProcessWithInvalidClass(): void
    {
        // 创建一个无效类的定义
        $definition = new Definition();
        $definition->setClass(null);
        $this->container->setDefinition('invalid_service', $definition);

        // 处理编译器传递应该不抛出异常
        $this->compilerPass->process($this->container);

        // 验证没有添加标签
        $this->assertFalse($definition->hasTag('doctrine.dedicated_entity_manager'));
    }
}

#[WithDedicatedEntityManager(channel: 'test_channel')]
class TestServiceWithAttribute
{
    // 测试用的服务类
}