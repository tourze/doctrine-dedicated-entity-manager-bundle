<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Attribute;

/**
 * 标记一个服务需要使用专用的 EntityManager
 * 
 * 使用示例：
 * ```php
 * #[WithDedicatedEntityManager('order')]
 * class OrderService
 * {
 *     public function __construct(
 *         private readonly EntityManagerInterface $entityManager
 *     ) {}
 * }
 * ```
 * 
 * 该注解会自动创建专用的 EntityManager 并注入到服务中
 * 数据库配置通过环境变量管理，例如：ORDER_DB_HOST, ORDER_DB_NAME 等
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class WithDedicatedEntityManager
{
    /**
     * @param string $channel EntityManager 通道名称，用于标识不同的数据库连接
     */
    public function __construct(
        public readonly string $channel
    ) {
    }
}
