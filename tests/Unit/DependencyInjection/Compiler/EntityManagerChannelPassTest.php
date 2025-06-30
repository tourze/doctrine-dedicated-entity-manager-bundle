<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Unit\DependencyInjection\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection\Compiler\EntityManagerChannelPass;
use Tourze\DoctrineDedicatedEntityManagerBundle\Exception\InvalidArgumentException;

class EntityManagerChannelPassTest extends TestCase
{
    private EntityManagerChannelPass $compilerPass;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->compilerPass = new EntityManagerChannelPass();
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

    public function testProcessWithValidChannel(): void
    {
        // 模拟必要的依赖服务
        $this->setupMockServices();
        
        // 创建一个带有有效标签的服务
        $definition = new Definition(TestServiceWithEntityManager::class);
        $definition->addTag('doctrine.dedicated_entity_manager', ['channel' => 'test_channel']);
        $this->container->setDefinition('test_service', $definition);

        // 处理编译器传递
        $this->compilerPass->process($this->container);

        // 验证参数是否被正确设置
        $arguments = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertEquals('doctrine.orm.test_channel_entity_manager', (string) $arguments[0]);
    }

    public function testProcessWithInvalidChannel(): void
    {
        // 创建一个没有 channel 属性的服务
        $definition = new Definition(\stdClass::class);
        $definition->addTag('doctrine.dedicated_entity_manager');
        $this->container->setDefinition('invalid_service', $definition);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "invalid_service" has a "doctrine.dedicated_entity_manager" tag without a "channel" attribute.');

        $this->compilerPass->process($this->container);
    }

    public function testProcessWithManagerRegistry(): void
    {
        // 模拟必要的依赖服务
        $this->setupMockServices();
        
        // 创建一个需要 ManagerRegistry 的服务
        $definition = new Definition(TestServiceWithRegistry::class);
        $definition->addTag('doctrine.dedicated_entity_manager', ['channel' => 'registry_channel']);
        $this->container->setDefinition('registry_service', $definition);

        // 处理编译器传递
        $this->compilerPass->process($this->container);

        // 验证参数是否被正确设置
        $arguments = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertEquals('doctrine.dedicated_registry.registry_channel', (string) $arguments[0]);
    }

    public function testProcessWithoutConstructor(): void
    {
        // 模拟必要的依赖服务
        $this->setupMockServices();
        
        // 创建一个没有构造函数的服务
        $definition = new Definition(TestServiceWithoutConstructor::class);
        $definition->addTag('doctrine.dedicated_entity_manager', ['channel' => 'no_constructor']);
        $this->container->setDefinition('no_constructor_service', $definition);

        // 处理编译器传递应该不抛出异常
        $this->compilerPass->process($this->container);

        // 验证服务定义仍然存在
        $this->assertTrue($this->container->hasDefinition('no_constructor_service'));
    }

    public function testProcessWithInvalidClassDefinition(): void
    {
        // 模拟必要的依赖服务
        $this->setupMockServices();
        
        // 创建一个无效类的定义
        $definition = new Definition();
        $definition->setClass(null);
        $definition->addTag('doctrine.dedicated_entity_manager', ['channel' => 'invalid']);
        $this->container->setDefinition('invalid_class_service', $definition);

        // 处理编译器传递应该不抛出异常
        $this->compilerPass->process($this->container);

        // 验证服务定义仍然存在
        $this->assertTrue($this->container->hasDefinition('invalid_class_service'));
    }
}

class TestServiceWithEntityManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}

class TestServiceWithRegistry
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }
    
    public function getRegistry(): ManagerRegistry
    {
        return $this->registry;
    }
}

class TestServiceWithoutConstructor
{
    // 没有构造函数的测试服务类
}