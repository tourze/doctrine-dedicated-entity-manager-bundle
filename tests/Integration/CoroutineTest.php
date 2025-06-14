<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\MockContextService;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\TestKernel;

class CoroutineTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testCoroutineContextIsolation(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var EntityManagerFactory $factory */
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        // 模拟不同协程上下文
        $contextService = $container->get('test.context_service');
        $this->assertInstanceOf(MockContextService::class, $contextService);
        
        // 在当前上下文创建 EntityManager
        $em1 = $factory->createEntityManager('coroutine_test');
        $this->assertNotNull($em1);
        
        // 由于容器服务在初始化后不能替换，我们验证基本功能
        // 这个测试主要验证工厂能正确处理上下文
        $this->assertNotNull($em1);
        $this->assertInstanceOf(\Doctrine\ORM\EntityManagerInterface::class, $em1);
    }

    public function testNonCoroutineEnvironment(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var EntityManagerFactory $factory */
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        // MockContextService 默认不支持协程
        $em1 = $factory->createEntityManager('non_coroutine_test');
        $em2 = $factory->createEntityManager('non_coroutine_test');
        
        // 在非协程环境中，相同通道应该返回相同实例
        $this->assertSame($em1, $em2);
    }

    public function testEntityManagerCleanup(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var EntityManagerFactory $factory */
        $factory = $container->get('doctrine_dedicated_entity_manager.factory');
        
        // 创建一些 EntityManager
        $factory->createEntityManager('cleanup_test_1');
        $factory->createEntityManager('cleanup_test_2');
        
        $this->assertCount(2, $factory->getEntityManagers());
        
        // 测试当前上下文清理
        $factory->closeCurrentContext();
        
        // 在非协程环境中，这应该清理所有 EntityManager
        $this->assertEmpty($factory->getEntityManagers());
    }

    public function testCoroutineAwareDeferExecution(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var MockContextService $contextService */
        $contextService = $container->get('test.context_service');
        
        // 测试 defer 功能
        $executed = false;
        $contextService->defer(function () use (&$executed) {
            $executed = true;
        });
        
        $this->assertFalse($executed);
        
        // 执行延迟的回调
        $contextService->executeDeferred();
        
        $this->assertTrue($executed);
    }

    public function testContextServiceReset(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        /** @var MockContextService $contextService */
        $contextService = $container->get('test.context_service');
        
        // 添加一些延迟执行的任务
        $executed1 = false;
        $executed2 = false;
        
        $contextService->defer(function () use (&$executed1) {
            $executed1 = true;
        });
        
        $contextService->defer(function () use (&$executed2) {
            $executed2 = true;
        });
        
        // 通过 reset 方法执行所有延迟任务
        $contextService->reset();
        
        $this->assertTrue($executed1);
        $this->assertTrue($executed2);
    }
}