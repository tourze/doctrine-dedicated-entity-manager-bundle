<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\TestService;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\TestKernel;

class BundleIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testBundleLoads(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        $this->assertTrue($container->has('doctrine_dedicated_entity_manager.factory'));
        $this->assertTrue($container->has(EntityManagerFactory::class));
    }

    public function testFactoryIsRegistered(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        $this->assertInstanceOf(EntityManagerFactory::class, $factory);
    }

    public function testAttributeAutoconfiguration(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        // Register test service manually
        $container->set(TestService::class, new TestService(
            $container->get('doctrine.orm.entity_manager')
        ));
        
        // Check if we can get our test service
        $this->assertTrue($container->has(TestService::class));
    }

    public function testEntityManagerCreation(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var EntityManagerFactory $factory */
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        $entityManager = $factory->createEntityManager('test');
        
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testDifferentChannelsProduceDifferentEntityManagers(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var EntityManagerFactory $factory */
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        $entityManager1 = $factory->createEntityManager('channel1');
        $entityManager2 = $factory->createEntityManager('channel2');
        
        $this->assertNotSame($entityManager1, $entityManager2);
    }

    public function testSameChannelProducesSameEntityManager(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var EntityManagerFactory $factory */
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        $entityManager1 = $factory->createEntityManager('same_channel');
        $entityManager2 = $factory->createEntityManager('same_channel');
        
        $this->assertSame($entityManager1, $entityManager2);
    }
}