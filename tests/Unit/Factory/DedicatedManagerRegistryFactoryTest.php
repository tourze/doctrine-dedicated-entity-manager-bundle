<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\DoctrineDedicatedConnectionBundle\Factory\DedicatedConnectionFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\DedicatedManagerRegistryFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Registry\DedicatedManagerRegistry;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\MockContextService;

class DedicatedManagerRegistryFactoryTest extends TestCase
{
    private EntityManagerFactory $entityManagerFactory;
    private DedicatedConnectionFactory $connectionFactory;
    private MockContextService $contextService;
    private LoggerInterface $logger;
    private DedicatedManagerRegistryFactory $factory;

    public function testCreateRegistry(): void
    {
        $registry = $this->factory->createRegistry('test_channel');

        $this->assertInstanceOf(DedicatedManagerRegistry::class, $registry);
        $this->assertEquals('test_channel', $registry->getChannel());
    }

    public function testCreateRegistryLogging(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'Creating dedicated ManagerRegistry for channel: {channel} in context: {context}',
                $this->callback(function ($context) {
                    return isset($context['channel']) && isset($context['context']);
                })
            );

        $this->factory->createRegistry('logged_channel');
    }

    public function testSameChannelReturnsSameRegistry(): void
    {
        $registry1 = $this->factory->createRegistry('same_channel');
        $registry2 = $this->factory->createRegistry('same_channel');

        $this->assertSame($registry1, $registry2);
    }

    public function testDifferentChannelsReturnDifferentRegistries(): void
    {
        $registry1 = $this->factory->createRegistry('channel1');
        $registry2 = $this->factory->createRegistry('channel2');

        $this->assertNotSame($registry1, $registry2);
        $this->assertEquals('channel1', $registry1->getChannel());
        $this->assertEquals('channel2', $registry2->getChannel());
    }

    public function testGetRegistries(): void
    {
        $this->assertEmpty($this->factory->getRegistries());

        $this->factory->createRegistry('test1');
        $this->factory->createRegistry('test2');

        $registries = $this->factory->getRegistries();
        $this->assertCount(2, $registries);
    }

    public function testCloseAll(): void
    {
        $this->factory->createRegistry('test1');
        $this->factory->createRegistry('test2');

        $this->assertCount(2, $this->factory->getRegistries());

        $this->factory->closeAll();

        $this->assertEmpty($this->factory->getRegistries());
    }

    public function testCloseAllWithSpecificContext(): void
    {
        // 创建第一个注册表
        $this->factory->createRegistry('test1');

        // 创建新的上下文服务模拟不同上下文
        $newContextService = new MockContextService();
        $newFactory = new DedicatedManagerRegistryFactory(
            $this->entityManagerFactory,
            $this->connectionFactory,
            $newContextService,
            $this->logger
        );

        // 在新上下文创建注册表
        $newFactory->createRegistry('test2');

        // 原工厂应该有 1 个注册表
        $this->assertCount(1, $this->factory->getRegistries());

        // 新工厂应该有 1 个注册表
        $this->assertCount(1, $newFactory->getRegistries());
    }

    public function testCloseCurrentContextInNonCoroutineEnvironment(): void
    {
        $this->factory->createRegistry('test1');
        $this->factory->createRegistry('test2');

        $this->assertCount(2, $this->factory->getRegistries());

        // 在非协程环境中，closeCurrentContext 应该关闭所有
        $this->factory->closeCurrentContext();

        $this->assertEmpty($this->factory->getRegistries());
    }

    public function testRegistryCreationWithDifferentChannels(): void
    {
        $channels = ['orders', 'users', 'logs', 'analytics'];
        $registries = [];

        foreach ($channels as $channel) {
            $registries[$channel] = $this->factory->createRegistry($channel);
        }

        // 验证所有注册表都是不同的实例
        foreach ($channels as $i => $channel1) {
            foreach ($channels as $j => $channel2) {
                if ($i !== $j) {
                    $this->assertNotSame($registries[$channel1], $registries[$channel2]);
                }
            }
        }

        // 验证每个注册表都有正确的通道
        foreach ($channels as $channel) {
            $this->assertEquals($channel, $registries[$channel]->getChannel());
        }
    }

    public function testFactoryWithNullLogger(): void
    {
        $factory = new DedicatedManagerRegistryFactory(
            $this->entityManagerFactory,
            $this->connectionFactory,
            $this->contextService
        );

        // 应该能正常创建注册表，不抛出异常
        $registry = $factory->createRegistry('null_logger_test');
        $this->assertInstanceOf(DedicatedManagerRegistry::class, $registry);
    }

    protected function setUp(): void
    {
        $this->entityManagerFactory = $this->createMock(EntityManagerFactory::class);
        $this->connectionFactory = $this->createMock(DedicatedConnectionFactory::class);
        $this->contextService = new MockContextService();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->factory = new DedicatedManagerRegistryFactory(
            $this->entityManagerFactory,
            $this->connectionFactory,
            $this->contextService,
            $this->logger
        );
    }
}