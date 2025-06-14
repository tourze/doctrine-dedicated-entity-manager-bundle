<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Registry;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\AbstractManagerRegistry;
use Tourze\DoctrineDedicatedConnectionBundle\Factory\DedicatedConnectionFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;

/**
 * 专用的 ManagerRegistry 实现
 * 为特定通道提供专用的 EntityManager
 */
class DedicatedManagerRegistry extends AbstractManagerRegistry
{
    public function __construct(
        private readonly string $channel,
        private readonly EntityManagerFactory $entityManagerFactory,
        private readonly DedicatedConnectionFactory $connectionFactory
    ) {
        $connections = [$channel => sprintf('doctrine.dbal.%s_connection', $channel)];
        $managers = [$channel => sprintf('doctrine.orm.%s_entity_manager', $channel)];
        
        parent::__construct(
            'Dedicated',
            $connections,
            $managers,
            $channel,
            $channel,
            'Doctrine\ORM\Proxy\Proxy'
        );
    }

    /**
     * 获取管理器名称列表
     */
    public function getManagerNames(): array
    {
        return [$this->channel => sprintf('doctrine.orm.%s_entity_manager', $this->channel)];
    }

    /**
     * 获取连接名称列表
     */
    public function getConnectionNames(): array
    {
        return [$this->channel => sprintf('doctrine.dbal.%s_connection', $this->channel)];
    }

    /**
     * 获取别名命名空间
     */
    public function getAliasNamespace($alias): string
    {
        // 对于专用注册表，我们不支持别名命名空间
        throw new \InvalidArgumentException(sprintf('Alias "%s" is not a valid alias in dedicated registry for channel "%s".', $alias, $this->channel));
    }

    /**
     * 获取默认管理器名称
     */
    public function getDefaultManagerName(): string
    {
        return $this->channel;
    }

    /**
     * 获取默认连接名称
     */
    public function getDefaultConnectionName(): string
    {
        return $this->channel;
    }

    /**
     * 获取通道名称
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * 获取服务实例
     */
    protected function getService($name): object
    {
        // 对于 EntityManager
        if (str_contains($name, '_entity_manager')) {
            return $this->getManager();
        }

        // 对于连接
        if (str_contains($name, '_connection')) {
            return $this->getConnection();
        }

        throw new \InvalidArgumentException(sprintf('Unknown service "%s" for channel "%s".', $name, $this->channel));
    }

    /**
     * 获取专用的 EntityManager
     */
    public function getManager($name = null): EntityManagerInterface
    {
        return $this->entityManagerFactory->createEntityManager($this->channel);
    }

    /**
     * 获取专用的连接
     */
    public function getConnection($name = null): Connection
    {
        return $this->connectionFactory->createConnection($this->channel);
    }

    /**
     * 重置服务
     */
    protected function resetService($name): void
    {
        // 对于 EntityManager
        if (str_contains($name, '_entity_manager')) {
            $this->resetManager();
            return;
        }

        // 连接不需要重置，因为它们由 connectionFactory 管理
    }

    /**
     * 重置管理器
     */
    public function resetManager($name = null): EntityManagerInterface
    {
        // 关闭当前 EntityManager 并创建新的
        $this->entityManagerFactory->closeAll();
        return $this->entityManagerFactory->createEntityManager($this->channel);
    }
}