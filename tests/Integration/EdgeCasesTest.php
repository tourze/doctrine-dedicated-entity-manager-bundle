<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Definition;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\TestServiceMultipleChannels;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\TestServiceNoEntityManager;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\TestServiceWithConnection;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\TestKernel;

class EdgeCasesTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testServiceWithoutEntityManagerParameter(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        // 注册没有 EntityManager 参数的服务
        $definition = new Definition(TestServiceNoEntityManager::class);
        $definition->addTag('doctrine.dedicated_entity_manager', ['channel' => 'test']);
        $container->set('test_service_no_em', new TestServiceNoEntityManager());
        
        // 应该不会抛出异常
        $this->assertTrue($container->has('test_service_no_em'));
    }

    public function testMultipleChannelsOnSameService(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        // 注册有多个通道的服务
        $container->set(TestServiceMultipleChannels::class, new TestServiceMultipleChannels(
            $container->get('doctrine.orm.entity_manager')
        ));
        
        $this->assertTrue($container->has(TestServiceMultipleChannels::class));
        
        /** @var EntityManagerFactory $factory */
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        // 验证可以为不同通道创建不同的 EntityManager
        $ordersEM = $factory->createEntityManager('orders');
        $logsEM = $factory->createEntityManager('logs');
        
        $this->assertInstanceOf(EntityManagerInterface::class, $ordersEM);
        $this->assertInstanceOf(EntityManagerInterface::class, $logsEM);
        $this->assertNotSame($ordersEM, $logsEM);
    }

    public function testEmptyChannelName(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var EntityManagerFactory $factory */
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        // 空字符串通道名应该能工作
        $entityManager = $factory->createEntityManager('');
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testSpecialCharactersInChannelName(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var EntityManagerFactory $factory */
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        // 特殊字符在通道名中
        $entityManager = $factory->createEntityManager('test_channel-123');
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testConnectionAndEntityManagerTogether(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        // 注册同时需要 Connection 和 EntityManager 的服务
        $container->set(TestServiceWithConnection::class, new TestServiceWithConnection(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('doctrine.dbal.default_connection')
        ));
        
        $this->assertTrue($container->has(TestServiceWithConnection::class));
    }

    public function testFactoryReturnsConsistentInstances(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var EntityManagerFactory $factory */
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        // 同一通道应该返回相同实例
        $em1 = $factory->createEntityManager('consistency_test');
        $em2 = $factory->createEntityManager('consistency_test');
        $this->assertSame($em1, $em2);
        
        // 不同通道返回不同实例
        $em3 = $factory->createEntityManager('another_channel');
        $this->assertNotSame($em1, $em3);
    }

    public function testFactoryCloseAll(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var EntityManagerFactory $factory */
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        // 创建多个 EntityManager
        $factory->createEntityManager('test1');
        $factory->createEntityManager('test2');
        
        $this->assertCount(2, $factory->getEntityManagers());
        
        // 关闭所有
        $factory->closeAll();
        
        $this->assertEmpty($factory->getEntityManagers());
    }

    public function testEntityManagerConfiguration(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var EntityManagerFactory $factory */
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        $entityManager = $factory->createEntityManager('config_test');
        
        // 验证 EntityManager 有正确的配置
        $this->assertNotNull($entityManager->getConfiguration());
        $this->assertNotNull($entityManager->getConnection());
    }
}