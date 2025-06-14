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

class EntityManagerFactoryTest extends TestCase
{
    private EntityManagerInterface $defaultEntityManager;
    private DedicatedConnectionFactory $connectionFactory;
    private MockContextService $contextService;
    private LoggerInterface $logger;
    private EntityManagerFactory $factory;

    public function testCreateEntityManagerReturnsSameInstanceForSameChannel(): void
    {
        // Mock connection from connection factory
        $connection = $this->createMock(Connection::class);
        $this->connectionFactory
            ->method('createConnection')
            ->with('test')
            ->willReturn($connection);

        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        $this->defaultEntityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManager1 = $this->factory->createEntityManager('test');
        $entityManager2 = $this->factory->createEntityManager('test');

        $this->assertSame($entityManager1, $entityManager2);
    }

    public function testCreateEntityManagerReturnsDifferentInstancesForDifferentChannels(): void
    {
        // Mock connections from connection factory
        $connection1 = $this->createMock(Connection::class);
        $connection2 = $this->createMock(Connection::class);
        $this->connectionFactory
            ->method('createConnection')
            ->willReturnMap([
                ['test1', $connection1],
                ['test2', $connection2],
            ]);

        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        $this->defaultEntityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManager1 = $this->factory->createEntityManager('test1');
        $entityManager2 = $this->factory->createEntityManager('test2');

        $this->assertNotSame($entityManager1, $entityManager2);
    }

    public function testGetEntityManagers(): void
    {
        // Mock connections from connection factory
        $connection1 = $this->createMock(Connection::class);
        $connection2 = $this->createMock(Connection::class);
        $this->connectionFactory
            ->method('createConnection')
            ->willReturnMap([
                ['test1', $connection1],
                ['test2', $connection2],
            ]);

        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        $this->defaultEntityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->assertEmpty($this->factory->getEntityManagers());

        $this->factory->createEntityManager('test1');
        $this->factory->createEntityManager('test2');

        $entityManagers = $this->factory->getEntityManagers();
        $this->assertCount(2, $entityManagers);
    }

    public function testCloseAll(): void
    {
        // Mock connections from connection factory
        $connection1 = $this->createMock(Connection::class);
        $connection2 = $this->createMock(Connection::class);
        $this->connectionFactory
            ->method('createConnection')
            ->willReturnMap([
                ['test1', $connection1],
                ['test2', $connection2],
            ]);

        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        $this->defaultEntityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->factory->createEntityManager('test1');
        $this->factory->createEntityManager('test2');

        $this->assertCount(2, $this->factory->getEntityManagers());

        $this->factory->closeAll();

        $this->assertEmpty($this->factory->getEntityManagers());
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