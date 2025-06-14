<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Unit\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\DoctrineDedicatedConnectionBundle\Factory\DedicatedConnectionFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\MockContextService;

class EntityManagerFactoryAdvancedTest extends TestCase
{
    private EntityManagerInterface $defaultEntityManager;
    private DedicatedConnectionFactory $connectionFactory;
    private MockContextService $contextService;
    private LoggerInterface $logger;
    private EntityManagerFactory $factory;

    public function testCreateEntityManagerUsesConnectionFactory(): void
    {
        // Mock connection from connection factory
        $connection = $this->createMock(Connection::class);
        $this->connectionFactory
            ->expects($this->once())
            ->method('createConnection')
            ->with('test_channel')
            ->willReturn($connection);

        // Mock default configuration
        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        $this->defaultEntityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManager = $this->factory->createEntityManager('test_channel');

        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testLoggingOnEntityManagerCreation(): void
    {
        // Mock connection
        $connection = $this->createMock(Connection::class);
        $this->connectionFactory
            ->method('createConnection')
            ->willReturn($connection);

        // Mock configuration
        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        $this->defaultEntityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        // Expect debug log
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'Creating dedicated EntityManager for channel: {channel} in context: {context}',
                $this->callback(function ($context) {
                    return isset($context['channel']) && isset($context['context']);
                })
            );

        $this->factory->createEntityManager('logged_channel');
    }

    public function testEntityManagerCaching(): void
    {
        // Mock connection
        $connection = $this->createMock(Connection::class);
        $this->connectionFactory
            ->expects($this->once()) // Should only be called once due to caching
            ->method('createConnection')
            ->willReturn($connection);

        // Mock configuration
        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        $this->defaultEntityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        $em1 = $this->factory->createEntityManager('cached_channel');
        $em2 = $this->factory->createEntityManager('cached_channel');

        $this->assertSame($em1, $em2);
    }

    public function testDifferentChannelsDifferentEntityManagers(): void
    {
        // Mock connections for different channels
        $connection1 = $this->createMock(Connection::class);
        $connection2 = $this->createMock(Connection::class);

        $this->connectionFactory
            ->expects($this->exactly(2))
            ->method('createConnection')
            ->willReturnMap([
                ['channel1', $connection1],
                ['channel2', $connection2],
            ]);

        // Mock configuration
        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        $this->defaultEntityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        $em1 = $this->factory->createEntityManager('channel1');
        $em2 = $this->factory->createEntityManager('channel2');

        $this->assertNotSame($em1, $em2);
    }

    public function testCloseEntityManagerLogging(): void
    {
        // Create an EntityManager first
        $connection = $this->createMock(Connection::class);
        $this->connectionFactory
            ->method('createConnection')
            ->willReturn($connection);

        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        $this->defaultEntityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        // Expect creation logging
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'Creating dedicated EntityManager for channel: {channel} in context: {context}',
                $this->callback(function ($context) {
                    return isset($context['channel']) && isset($context['context']);
                })
            );

        $this->factory->createEntityManager('test_close');

        // Verify EntityManager was created
        $this->assertCount(1, $this->factory->getEntityManagers());

        $this->factory->closeAll();

        // Verify EntityManager was closed
        $this->assertEmpty($this->factory->getEntityManagers());
    }

    public function testCloseAllWithSpecificContext(): void
    {
        // Create EntityManagers in different mock contexts
        $connection = $this->createMock(Connection::class);
        $this->connectionFactory
            ->method('createConnection')
            ->willReturn($connection);

        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        $this->defaultEntityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        // Create first EntityManager
        $this->factory->createEntityManager('test1');

        // Create a new context service to simulate different context
        $newContextService = new MockContextService();
        $newFactory = new EntityManagerFactory(
            $this->defaultEntityManager,
            $this->connectionFactory,
            $newContextService,
            $this->logger
        );

        // Create EntityManager in new context
        $newFactory->createEntityManager('test2');

        // Original factory should have 1 EntityManager
        $this->assertCount(1, $this->factory->getEntityManagers());

        // New factory should have 1 EntityManager
        $this->assertCount(1, $newFactory->getEntityManagers());
    }

    public function testCloseCurrentContextInNonCoroutineEnvironment(): void
    {
        // Mock connection
        $connection = $this->createMock(Connection::class);
        $this->connectionFactory
            ->method('createConnection')
            ->willReturn($connection);

        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        $this->defaultEntityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        // Create some EntityManagers
        $this->factory->createEntityManager('test1');
        $this->factory->createEntityManager('test2');

        $this->assertCount(2, $this->factory->getEntityManagers());

        // In non-coroutine environment, closeCurrentContext should close all
        $this->factory->closeCurrentContext();

        $this->assertEmpty($this->factory->getEntityManagers());
    }

    public function testEntityManagerConfiguration(): void
    {
        // Mock connection
        $connection = $this->createMock(Connection::class);
        $this->connectionFactory
            ->method('createConnection')
            ->willReturn($connection);

        // Use a real configuration to test
        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        $this->defaultEntityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManager = $this->factory->createEntityManager('config_test');

        // The created EntityManager should use the same configuration as the default
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    protected function setUp(): void
    {
        $this->defaultEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->connectionFactory = $this->createMock(DedicatedConnectionFactory::class);
        $this->contextService = new MockContextService();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->factory = new EntityManagerFactory(
            $this->defaultEntityManager,
            $this->connectionFactory,
            $this->contextService,
            $this->logger
        );
    }
}