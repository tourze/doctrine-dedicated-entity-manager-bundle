<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Integration\CompilerPass;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection\Compiler\EntityManagerChannelPass;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\TestServiceWithTag;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\TestKernel;

class EntityManagerChannelPassTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testProcessCreatesEntityManagerService(): void
    {
        self::bootKernel();
        
        $container = new ContainerBuilder();
        
        // Mock the factory services
        $factoryDefinition = new Definition(EntityManagerFactory::class);
        $container->setDefinition('doctrine_dedicated_entity_manager.factory', $factoryDefinition);
        
        $connectionFactoryDefinition = new Definition('SomeConnectionFactory');
        $container->setDefinition('doctrine_dedicated_connection.factory', $connectionFactoryDefinition);
        
        // Create a service with the tag
        $serviceDefinition = new Definition(TestServiceWithTag::class);
        $serviceDefinition->addTag('doctrine.dedicated_entity_manager', ['channel' => 'test']);
        $container->setDefinition('test_service', $serviceDefinition);
        
        $pass = new EntityManagerChannelPass();
        $pass->process($container);
        
        // Check if both connection and EntityManager services were created
        $this->assertTrue($container->hasDefinition('doctrine.dbal.test_connection'));
        $this->assertTrue($container->hasDefinition('doctrine.orm.test_entity_manager'));
        
        $entityManagerDefinition = $container->getDefinition('doctrine.orm.test_entity_manager');
        $this->assertEquals(EntityManagerInterface::class, $entityManagerDefinition->getClass());
    }

    public function testProcessThrowsExceptionForMissingChannel(): void
    {
        $container = new ContainerBuilder();
        
        // Mock the factory service
        $factoryDefinition = new Definition(EntityManagerFactory::class);
        $container->setDefinition('doctrine_dedicated_entity_manager.factory', $factoryDefinition);
        
        // Create a service without channel
        $serviceDefinition = new Definition(TestServiceWithTag::class);
        $serviceDefinition->addTag('doctrine.dedicated_entity_manager');
        $container->setDefinition('test_service', $serviceDefinition);
        
        $pass = new EntityManagerChannelPass();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "test_service" has a "doctrine.dedicated_entity_manager" tag without a "channel" attribute.');
        
        $pass->process($container);
    }

    public function testProcessSkipsWhenNoTaggedServices(): void
    {
        $container = new ContainerBuilder();
        
        $pass = new EntityManagerChannelPass();
        $pass->process($container);
        
        // Should not throw exception or create any services
        $this->assertTrue(true);
    }
}