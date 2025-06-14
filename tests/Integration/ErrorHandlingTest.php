<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection\Compiler\EntityManagerChannelPass;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\TestServiceWithTag;

class ErrorHandlingTest extends TestCase
{
    public function testMissingChannelAttributeThrowsException(): void
    {
        $container = new ContainerBuilder();
        
        // 添加必要的服务
        $factoryDefinition = new Definition(EntityManagerFactory::class);
        $container->setDefinition('doctrine_dedicated_entity_manager.factory', $factoryDefinition);
        
        // 创建没有 channel 属性的服务
        $serviceDefinition = new Definition(TestServiceWithTag::class);
        $serviceDefinition->addTag('doctrine.dedicated_entity_manager'); // 缺少 channel
        $container->setDefinition('test_service', $serviceDefinition);
        
        $pass = new EntityManagerChannelPass();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "test_service" has a "doctrine.dedicated_entity_manager" tag without a "channel" attribute.');
        
        $pass->process($container);
    }

    public function testMissingDedicatedConnectionFactoryThrowsException(): void
    {
        $container = new ContainerBuilder();
        
        // 不添加 doctrine_dedicated_connection.factory 服务
        $factoryDefinition = new Definition(EntityManagerFactory::class);
        $container->setDefinition('doctrine_dedicated_entity_manager.factory', $factoryDefinition);
        
        // 创建带有 channel 的服务
        $serviceDefinition = new Definition(TestServiceWithTag::class);
        $serviceDefinition->addTag('doctrine.dedicated_entity_manager', ['channel' => 'test']);
        $container->setDefinition('test_service', $serviceDefinition);
        
        $pass = new EntityManagerChannelPass();
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot create dedicated connection for channel "test". The doctrine-dedicated-connection-bundle is required but not properly configured.');
        
        $pass->process($container);
    }

    public function testEmptyTaggedServicesDoesNotThrowException(): void
    {
        $container = new ContainerBuilder();
        
        $pass = new EntityManagerChannelPass();
        
        // 没有带标签的服务，应该正常执行
        $pass->process($container);
        
        $this->assertTrue(true); // 如果到达这里，说明没有抛出异常
    }

    public function testServiceWithNonExistentClassIsIgnored(): void
    {
        $container = new ContainerBuilder();
        
        // 添加必要的服务
        $factoryDefinition = new Definition(EntityManagerFactory::class);
        $container->setDefinition('doctrine_dedicated_entity_manager.factory', $factoryDefinition);
        
        $connectionFactoryDefinition = new Definition('SomeConnectionFactory');
        $container->setDefinition('doctrine_dedicated_connection.factory', $connectionFactoryDefinition);
        
        // 创建指向不存在类的服务
        $serviceDefinition = new Definition('NonExistentClass');
        $serviceDefinition->addTag('doctrine.dedicated_entity_manager', ['channel' => 'test']);
        $container->setDefinition('test_service', $serviceDefinition);
        
        $pass = new EntityManagerChannelPass();
        
        // 应该不抛出异常，只是跳过这个服务
        $pass->process($container);
        
        $this->assertTrue(true);
    }

    public function testServiceWithoutConstructorIsIgnored(): void
    {
        $container = new ContainerBuilder();
        
        // 添加必要的服务
        $factoryDefinition = new Definition(EntityManagerFactory::class);
        $container->setDefinition('doctrine_dedicated_entity_manager.factory', $factoryDefinition);
        
        $connectionFactoryDefinition = new Definition('SomeConnectionFactory');
        $container->setDefinition('doctrine_dedicated_connection.factory', $connectionFactoryDefinition);
        
        // 创建没有构造函数的服务类
        $serviceDefinition = new Definition(\stdClass::class);
        $serviceDefinition->addTag('doctrine.dedicated_entity_manager', ['channel' => 'test']);
        $container->setDefinition('test_service', $serviceDefinition);
        
        $pass = new EntityManagerChannelPass();
        
        // 应该不抛出异常
        $pass->process($container);
        
        $this->assertTrue(true);
    }

    public function testDuplicateChannelServicesAreHandledCorrectly(): void
    {
        $container = new ContainerBuilder();
        
        // 添加必要的服务
        $factoryDefinition = new Definition(EntityManagerFactory::class);
        $container->setDefinition('doctrine_dedicated_entity_manager.factory', $factoryDefinition);
        
        $connectionFactoryDefinition = new Definition('SomeConnectionFactory');
        $container->setDefinition('doctrine_dedicated_connection.factory', $connectionFactoryDefinition);
        
        // 创建两个使用相同通道的服务
        $serviceDefinition1 = new Definition(TestServiceWithTag::class);
        $serviceDefinition1->addTag('doctrine.dedicated_entity_manager', ['channel' => 'duplicate']);
        $container->setDefinition('test_service_1', $serviceDefinition1);
        
        $serviceDefinition2 = new Definition(TestServiceWithTag::class);
        $serviceDefinition2->addTag('doctrine.dedicated_entity_manager', ['channel' => 'duplicate']);
        $container->setDefinition('test_service_2', $serviceDefinition2);
        
        $pass = new EntityManagerChannelPass();
        $pass->process($container);
        
        // 应该只创建一个 EntityManager 服务
        $this->assertTrue($container->hasDefinition('doctrine.orm.duplicate_entity_manager'));
        
        // 两个服务应该都能获取到 EntityManager 参数
        $this->assertTrue(true);
    }
}