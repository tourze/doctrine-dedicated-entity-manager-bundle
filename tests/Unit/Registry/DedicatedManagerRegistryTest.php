<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Unit\Registry;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineDedicatedConnectionBundle\Factory\DedicatedConnectionFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Registry\DedicatedManagerRegistry;

class DedicatedManagerRegistryTest extends TestCase
{
    private EntityManagerFactory $entityManagerFactory;
    private DedicatedConnectionFactory $connectionFactory;
    private DedicatedManagerRegistry $registry;

    public function testGetChannel(): void
    {
        $this->assertEquals('test_channel', $this->registry->getChannel());
    }

    public function testGetManager(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->entityManagerFactory
            ->expects($this->once())
            ->method('createEntityManager')
            ->with('test_channel')
            ->willReturn($entityManager);

        $result = $this->registry->getManager();
        $this->assertSame($entityManager, $result);
    }

    public function testGetConnection(): void
    {
        $connection = $this->createMock(Connection::class);

        $this->connectionFactory
            ->expects($this->once())
            ->method('createConnection')
            ->with('test_channel')
            ->willReturn($connection);

        $result = $this->registry->getConnection();
        $this->assertSame($connection, $result);
    }

    public function testGetManagerNames(): void
    {
        $expected = ['test_channel' => 'doctrine.orm.test_channel_entity_manager'];
        $this->assertEquals($expected, $this->registry->getManagerNames());
    }

    public function testGetConnectionNames(): void
    {
        $expected = ['test_channel' => 'doctrine.dbal.test_channel_connection'];
        $this->assertEquals($expected, $this->registry->getConnectionNames());
    }

    public function testGetDefaultManagerName(): void
    {
        $this->assertEquals('test_channel', $this->registry->getDefaultManagerName());
    }

    public function testGetDefaultConnectionName(): void
    {
        $this->assertEquals('test_channel', $this->registry->getDefaultConnectionName());
    }

    public function testResetManager(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->entityManagerFactory
            ->expects($this->once())
            ->method('closeAll');

        $this->entityManagerFactory
            ->expects($this->once())
            ->method('createEntityManager')
            ->with('test_channel')
            ->willReturn($entityManager);

        $result = $this->registry->resetManager();
        $this->assertSame($entityManager, $result);
    }

    public function testGetAliasNamespaceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Alias "test_alias" is not a valid alias in dedicated registry for channel "test_channel".');

        $this->registry->getAliasNamespace('test_alias');
    }

    public function testGetServiceWithEntityManager(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->entityManagerFactory
            ->expects($this->once())
            ->method('createEntityManager')
            ->with('test_channel')
            ->willReturn($entityManager);

        // 使用 reflection 来测试 protected 方法
        $reflection = new \ReflectionClass($this->registry);
        $method = $reflection->getMethod('getService');
        $method->setAccessible(true);

        $result = $method->invoke($this->registry, 'test_channel_entity_manager');
        $this->assertSame($entityManager, $result);
    }

    public function testGetServiceWithConnection(): void
    {
        $connection = $this->createMock(Connection::class);

        $this->connectionFactory
            ->expects($this->once())
            ->method('createConnection')
            ->with('test_channel')
            ->willReturn($connection);

        // 使用 reflection 来测试 protected 方法
        $reflection = new \ReflectionClass($this->registry);
        $method = $reflection->getMethod('getService');
        $method->setAccessible(true);

        $result = $method->invoke($this->registry, 'test_channel_connection');
        $this->assertSame($connection, $result);
    }

    public function testGetServiceWithUnknownServiceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown service "unknown_service" for channel "test_channel".');

        // 使用 reflection 来测试 protected 方法
        $reflection = new \ReflectionClass($this->registry);
        $method = $reflection->getMethod('getService');
        $method->setAccessible(true);

        $method->invoke($this->registry, 'unknown_service');
    }

    public function testResetServiceWithEntityManager(): void
    {
        $this->entityManagerFactory
            ->expects($this->once())
            ->method('closeAll');

        $this->entityManagerFactory
            ->expects($this->once())
            ->method('createEntityManager')
            ->with('test_channel')
            ->willReturn($this->createMock(EntityManagerInterface::class));

        // 使用 reflection 来测试 protected 方法
        $reflection = new \ReflectionClass($this->registry);
        $method = $reflection->getMethod('resetService');
        $method->setAccessible(true);

        $method->invoke($this->registry, 'test_channel_entity_manager');
    }

    public function testResetServiceWithConnection(): void
    {
        // Connection 重置应该不做任何操作（由 connectionFactory 管理）

        // 使用 reflection 来测试 protected 方法
        $reflection = new \ReflectionClass($this->registry);
        $method = $reflection->getMethod('resetService');
        $method->setAccessible(true);

        // 应该不抛出异常
        $method->invoke($this->registry, 'test_channel_connection');
        $this->addToAssertionCount(1);
    }

    protected function setUp(): void
    {
        $this->entityManagerFactory = $this->createMock(EntityManagerFactory::class);
        $this->connectionFactory = $this->createMock(DedicatedConnectionFactory::class);

        $this->registry = new DedicatedManagerRegistry(
            'test_channel',
            $this->entityManagerFactory,
            $this->connectionFactory
        );
    }
}