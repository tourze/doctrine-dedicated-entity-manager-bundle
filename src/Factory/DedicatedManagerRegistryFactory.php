<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Factory;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tourze\DoctrineDedicatedConnectionBundle\Factory\DedicatedConnectionFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Registry\DedicatedManagerRegistry;
use Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface;

/**
 * 专用 ManagerRegistry 工厂
 * 负责创建和管理多个独立的 ManagerRegistry 实例
 */
class DedicatedManagerRegistryFactory
{
    /**
     * @var array<string, DedicatedManagerRegistry>
     */
    private array $registries = [];
    private LoggerInterface $logger;

    public function __construct(
        private readonly EntityManagerFactory $entityManagerFactory,
        private readonly DedicatedConnectionFactory $connectionFactory,
        private readonly ContextServiceInterface $contextService,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * 创建或获取专用的 ManagerRegistry
     */
    public function createRegistry(string $channel): DedicatedManagerRegistry
    {
        // 获取上下文相关的注册表键
        $contextKey = $this->getContextKey($channel);

        if (isset($this->registries[$contextKey])) {
            return $this->registries[$contextKey];
        }

        $this->logger->debug('Creating dedicated ManagerRegistry for channel: {channel} in context: {context}', [
            'channel' => $channel,
            'context' => $this->contextService->getId()
        ]);

        $registry = new DedicatedManagerRegistry(
            $channel,
            $this->entityManagerFactory,
            $this->connectionFactory
        );

        $this->registries[$contextKey] = $registry;

        // 在协程环境中，注册清理回调
        if ($this->contextService->supportCoroutine()) {
            $this->contextService->defer(function () use ($contextKey) {
                $this->closeRegistry($contextKey);
            });
        }

        return $registry;
    }

    /**
     * 获取上下文相关的注册表键
     */
    private function getContextKey(string $channel): string
    {
        if ($this->contextService->supportCoroutine()) {
            return $this->contextService->getId() . ':' . $channel;
        }

        return $channel;
    }

    /**
     * 关闭指定的注册表
     */
    private function closeRegistry(string $contextKey): void
    {
        if (isset($this->registries[$contextKey])) {
            $this->logger->debug('Closing dedicated ManagerRegistry: {contextKey}', ['contextKey' => $contextKey]);
            unset($this->registries[$contextKey]);
        }
    }

    /**
     * 获取所有已创建的注册表
     *
     * @return array<string, DedicatedManagerRegistry>
     */
    public function getRegistries(): array
    {
        return $this->registries;
    }

    /**
     * 关闭当前上下文的所有注册表
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
     * 关闭所有注册表
     */
    public function closeAll(?string $contextId = null): void
    {
        if ($contextId === null) {
            // 关闭所有注册表
            $this->registries = [];
        } else {
            // 只关闭指定上下文的注册表
            $prefix = $contextId . ':';
            $toClose = [];

            foreach (array_keys($this->registries) as $key) {
                if (str_starts_with($key, $prefix)) {
                    $toClose[] = $key;
                }
            }

            foreach ($toClose as $key) {
                $this->closeRegistry($key);
            }
        }
    }
}