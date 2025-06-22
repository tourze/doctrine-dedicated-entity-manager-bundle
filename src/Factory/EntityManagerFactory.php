<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tourze\DoctrineDedicatedConnectionBundle\Factory\DedicatedConnectionFactory;
use Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface;

/**
 * 专用 EntityManager 工厂
 * 负责创建和管理多个独立的 EntityManager 实例
 */
class EntityManagerFactory
{
    /**
     * @var array<string, EntityManagerInterface>
     */
    private array $entityManagers = [];
    private LoggerInterface $logger;

    public function __construct(
        private readonly EntityManagerInterface $defaultEntityManager,
        private readonly DedicatedConnectionFactory $connectionFactory,
        private readonly ContextServiceInterface $contextService,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * 创建或获取专用的 EntityManager
     * 在协程环境中，每个协程上下文都会有独立的 EntityManager 池
     */
    public function createEntityManager(string $channel): EntityManagerInterface
    {
        // 获取上下文相关的 EntityManager 键
        $contextKey = $this->getContextKey($channel);

        $existingEntityManager = $this->entityManagers[$contextKey] ?? null;
        if ($existingEntityManager instanceof EntityManagerInterface) {
            return $existingEntityManager;
        }

        $this->logger->debug('Creating dedicated EntityManager for channel: {channel} in context: {context}', [
            'channel' => $channel,
            'context' => $this->contextService->getId()
        ]);

        // 使用 DedicatedConnectionFactory 创建专用连接
        $connection = $this->connectionFactory->createConnection($channel);
        
        // 获取默认 EntityManager 的配置
        $defaultConfiguration = $this->defaultEntityManager->getConfiguration();

        // 创建新的 EntityManager
        $entityManager = new EntityManager($connection, $defaultConfiguration);

        $this->entityManagers[$contextKey] = $entityManager;

        // 在协程环境中，注册 EntityManager 清理回调
        if ($this->contextService->supportCoroutine()) {
            $this->contextService->defer(function () use ($contextKey) {
                $this->closeEntityManager($contextKey);
            });
        }

        return $entityManager;
    }


    /**
     * 获取上下文相关的 EntityManager 键
     */
    private function getContextKey(string $channel): string
    {
        if ($this->contextService->supportCoroutine()) {
            return $this->contextService->getId() . ':' . $channel;
        }

        return $channel;
    }

    /**
     * 关闭指定的 EntityManager
     */
    private function closeEntityManager(string $contextKey): void
    {
        $entityManager = $this->entityManagers[$contextKey] ?? null;
        if ($entityManager instanceof EntityManagerInterface) {
            $this->logger->debug('Closing dedicated EntityManager: {contextKey}', ['contextKey' => $contextKey]);
            $entityManager->close();
            unset($this->entityManagers[$contextKey]);
        }
    }

    /**
     * 获取所有已创建的 EntityManager
     *
     * @return array<string, EntityManagerInterface>
     */
    public function getEntityManagers(): array
    {
        return $this->entityManagers;
    }

    /**
     * 关闭当前上下文的所有 EntityManager
     */
    public function closeCurrentContext(): void
    {
        if ($this->contextService->supportCoroutine()) {
            $this->closeAll($this->contextService->getId());
        } else {
            $this->closeAll();
        }
    }

    /**
     * 关闭所有 EntityManager，或者只关闭当前上下文的 EntityManager
     */
    public function closeAll(?string $contextId = null): void
    {
        if ($contextId === null) {
            // 关闭所有 EntityManager
            foreach ($this->entityManagers as $entityManager) {
                if ($entityManager instanceof EntityManagerInterface) {
                    $entityManager->close();
                }
            }
            $this->entityManagers = [];
        } else {
            // 只关闭指定上下文的 EntityManager
            $prefix = $contextId . ':';
            $toClose = [];

            foreach (array_keys($this->entityManagers) as $key) {
                if (str_starts_with($key, $prefix)) {
                    $toClose[] = $key;
                }
            }

            foreach ($toClose as $key) {
                $this->closeEntityManager($key);
            }
        }
    }
}