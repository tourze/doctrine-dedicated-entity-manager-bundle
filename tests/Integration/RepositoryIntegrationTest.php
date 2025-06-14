<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\DedicatedManagerRegistryFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Registry\DedicatedManagerRegistry;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\TestKernel;

class RepositoryIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testManagerRegistryFactoryIsRegistered(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        $this->assertTrue($container->has('doctrine_dedicated_manager_registry.factory'));
        $this->assertTrue($container->has(DedicatedManagerRegistryFactory::class));
        
        $factory = $container->get('doctrine_dedicated_manager_registry.factory');
        $this->assertInstanceOf(DedicatedManagerRegistryFactory::class, $factory);
    }

    public function testDedicatedManagerRegistryCreation(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var DedicatedManagerRegistryFactory $factory */
        $factory = $container->get('doctrine_dedicated_manager_registry.factory');
        
        $registry = $factory->createRegistry('test_channel');
        
        $this->assertInstanceOf(DedicatedManagerRegistry::class, $registry);
        $this->assertEquals('test_channel', $registry->getChannel());
    }

    public function testDifferentChannelsDifferentRegistries(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var DedicatedManagerRegistryFactory $factory */
        $factory = $container->get('doctrine_dedicated_manager_registry.factory');
        
        $registry1 = $factory->createRegistry('channel1');
        $registry2 = $factory->createRegistry('channel2');
        
        $this->assertNotSame($registry1, $registry2);
        $this->assertEquals('channel1', $registry1->getChannel());
        $this->assertEquals('channel2', $registry2->getChannel());
    }

    public function testSameChannelSameRegistry(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var DedicatedManagerRegistryFactory $factory */
        $factory = $container->get('doctrine_dedicated_manager_registry.factory');
        
        $registry1 = $factory->createRegistry('same_channel');
        $registry2 = $factory->createRegistry('same_channel');
        
        $this->assertSame($registry1, $registry2);
    }

    public function testDedicatedManagerRegistryReturnsCorrectEntityManager(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var DedicatedManagerRegistryFactory $registryFactory */
        $registryFactory = $container->get('doctrine_dedicated_manager_registry.factory');
        
        /** @var EntityManagerFactory $emFactory */
        $emFactory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        $registry = $registryFactory->createRegistry('test_em');
        $directEM = $emFactory->createEntityManager('test_em');
        
        // 注册表应该返回相同的 EntityManager 实例
        $registryEM = $registry->getManager();
        $this->assertSame($directEM, $registryEM);
    }

    public function testDedicatedManagerRegistryConnection(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var DedicatedManagerRegistryFactory $factory */
        $factory = $container->get('doctrine_dedicated_manager_registry.factory');
        
        $registry = $factory->createRegistry('test_connection');
        
        $connection = $registry->getConnection();
        $this->assertNotNull($connection);
        
        // 通过 EntityManager 获取的连接应该是同一个
        $emConnection = $registry->getManager()->getConnection();
        $this->assertSame($connection, $emConnection);
    }

    public function testRepositoryWithDedicatedManagerRegistry(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var DedicatedManagerRegistryFactory $factory */
        $factory = $container->get('doctrine_dedicated_manager_registry.factory');
        
        $registry = $factory->createRegistry('test_repo');
        
        // 验证 ManagerRegistry 可以正常工作
        $this->assertInstanceOf(DedicatedManagerRegistry::class, $registry);
        $this->assertEquals('test_repo', $registry->getChannel());
        
        // 验证可以获取 EntityManager
        $entityManager = $registry->getManager();
        $this->assertNotNull($entityManager);
        
        // 验证可以获取连接
        $connection = $registry->getConnection();
        $this->assertNotNull($connection);
    }

    public function testMultipleRegistriesWithDifferentChannels(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var DedicatedManagerRegistryFactory $factory */
        $factory = $container->get('doctrine_dedicated_manager_registry.factory');
        
        // 创建使用不同通道的 ManagerRegistry
        $registry1 = $factory->createRegistry('repo_channel_1');
        $registry2 = $factory->createRegistry('repo_channel_2');
        
        // 验证它们是不同的实例
        $this->assertNotSame($registry1, $registry2);
        $this->assertEquals('repo_channel_1', $registry1->getChannel());
        $this->assertEquals('repo_channel_2', $registry2->getChannel());
        
        // 验证它们使用不同的 EntityManager
        $em1 = $registry1->getManager();
        $em2 = $registry2->getManager();
        
        $this->assertNotSame($em1, $em2);
        
        // 验证它们使用不同的连接
        $conn1 = $registry1->getConnection();
        $conn2 = $registry2->getConnection();
        
        $this->assertNotSame($conn1, $conn2);
    }

    public function testManagerRegistryDefaultNames(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var DedicatedManagerRegistryFactory $factory */
        $factory = $container->get('doctrine_dedicated_manager_registry.factory');
        
        $registry = $factory->createRegistry('test_defaults');
        
        $this->assertEquals('test_defaults', $registry->getDefaultManagerName());
        $this->assertEquals('test_defaults', $registry->getDefaultConnectionName());
        
        $managerNames = $registry->getManagerNames();
        $this->assertArrayHasKey('test_defaults', $managerNames);
        
        $connectionNames = $registry->getConnectionNames();
        $this->assertArrayHasKey('test_defaults', $connectionNames);
    }

    public function testManagerRegistryReset(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var DedicatedManagerRegistryFactory $factory */
        $factory = $container->get('doctrine_dedicated_manager_registry.factory');
        
        $registry = $factory->createRegistry('test_reset');
        
        $originalEM = $registry->getManager();
        $resetEM = $registry->resetManager();
        
        // Reset 应该返回一个新的 EntityManager 实例
        $this->assertNotSame($originalEM, $resetEM);
        
        // 后续调用应该返回新的实例
        $currentEM = $registry->getManager();
        $this->assertSame($resetEM, $currentEM);
    }

    public function testManagerRegistryFactoryCloseAll(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var DedicatedManagerRegistryFactory $factory */
        $factory = $container->get('doctrine_dedicated_manager_registry.factory');
        
        // 创建多个注册表
        $factory->createRegistry('test_close_1');
        $factory->createRegistry('test_close_2');
        
        $this->assertCount(2, $factory->getRegistries());
        
        // 关闭所有
        $factory->closeAll();
        
        $this->assertEmpty($factory->getRegistries());
    }
}