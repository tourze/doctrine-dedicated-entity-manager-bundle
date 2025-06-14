<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection\Compiler;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\DoctrineDedicatedEntityManagerBundle\Registry\DedicatedManagerRegistry;

/**
 * 共享的 EntityManager 创建逻辑
 */
trait EntityManagerCreationTrait
{
    /**
     * 确保 EntityManager 服务存在
     */
    protected function ensureEntityManagerService(ContainerBuilder $container, string $channel, array $attributes = []): void
    {
        $entityManagerId = sprintf('doctrine.orm.%s_entity_manager', $channel);

        // 如果 EntityManager 已存在，直接返回
        if ($container->hasDefinition($entityManagerId) || $container->hasAlias($entityManagerId)) {
            return;
        }

        // 确保对应的专用连接也存在
        $this->ensureConnectionService($container, $channel, $attributes);

        // 创建 EntityManager 服务
        $this->createEntityManagerService($container, $channel);
        
        // 创建专用 ManagerRegistry 服务
        $this->createManagerRegistryService($container, $channel);
    }

    /**
     * 确保连接服务存在（委托给 doctrine-dedicated-connection-bundle）
     */
    protected function ensureConnectionService(ContainerBuilder $container, string $channel, array $attributes = []): void
    {
        $connectionId = sprintf('doctrine.dbal.%s_connection', $channel);
        
        // 如果连接已存在，直接返回
        if ($container->hasDefinition($connectionId) || $container->hasAlias($connectionId)) {
            return;
        }

        // 检查是否存在 dedicated connection factory
        if (!$container->hasDefinition('doctrine_dedicated_connection.factory') && 
            !$container->hasAlias('doctrine_dedicated_connection.factory')) {
            throw new \RuntimeException(sprintf(
                'Cannot create dedicated connection for channel "%s". ' .
                'The doctrine-dedicated-connection-bundle is required but not properly configured.',
                $channel
            ));
        }

        $factory = new Reference('doctrine_dedicated_connection.factory');
        
        // 创建连接定义
        $connectionDef = new Definition(Connection::class);
        $connectionDef->setFactory([$factory, 'createConnection']);
        $connectionDef->setArguments([$channel]);
        $connectionDef->setPublic(false);
        $connectionDef->addTag('doctrine.connection');
        
        $container->setDefinition($connectionId, $connectionDef);
    }

    /**
     * 创建 EntityManager 服务
     */
    protected function createEntityManagerService(ContainerBuilder $container, string $channel): void
    {
        $entityManagerId = sprintf('doctrine.orm.%s_entity_manager', $channel);
        
        if ($container->hasDefinition($entityManagerId) || $container->hasAlias($entityManagerId)) {
            return;
        }

        $factory = new Reference('doctrine_dedicated_entity_manager.factory');

        // 创建 EntityManager 定义
        $entityManagerDef = new Definition(EntityManagerInterface::class);
        $entityManagerDef->setFactory([$factory, 'createEntityManager']);
        $entityManagerDef->setArguments([$channel]);
        $entityManagerDef->setPublic(false);
        $entityManagerDef->addTag('doctrine.entity_manager');

        $container->setDefinition($entityManagerId, $entityManagerDef);
    }

    /**
     * 创建专用 ManagerRegistry 服务
     */
    protected function createManagerRegistryService(ContainerBuilder $container, string $channel): void
    {
        $registryId = sprintf('doctrine.dedicated_registry.%s', $channel);
        
        if ($container->hasDefinition($registryId) || $container->hasAlias($registryId)) {
            return;
        }

        $factory = new Reference('doctrine_dedicated_manager_registry.factory');

        // 创建 ManagerRegistry 定义
        $registryDef = new Definition(DedicatedManagerRegistry::class);
        $registryDef->setFactory([$factory, 'createRegistry']);
        $registryDef->setArguments([$channel]);
        $registryDef->setPublic(false);

        $container->setDefinition($registryId, $registryDef);
    }
}
