# Doctrine Dedicated EntityManager Bundle

自动化 Doctrine EntityManager 服务注册的 Symfony Bundle，通过注解和编译器通道实现专用 EntityManager 的自动创建和注入。

## 功能特点

- **注解驱动**: 使用 `#[WithDedicatedEntityManager('channel')]` 注解自动注册专用 EntityManager
- **自动服务注册**: 通过 CompilerPass 自动创建和配置 EntityManager 服务
- **连接集成**: 与 `doctrine-dedicated-connection-bundle` 深度集成，自动创建对应的专用连接
- **环境变量配置**: 支持通过环境变量配置不同通道的数据库连接
- **协程支持**: 在协程环境中自动管理 EntityManager 生命周期
- **向后兼容**: 支持手动服务标签配置

## 安装

```bash
composer require tourze/doctrine-dedicated-entity-manager-bundle
```

## 配置

在 `bundles.php` 中注册 Bundle：

```php
return [
    // ...
    Tourze\DoctrineDedicatedConnectionBundle\DoctrineDedicatedConnectionBundle::class => ['all' => true],
    Tourze\DoctrineDedicatedEntityManagerBundle\DoctrineDedicatedEntityManagerBundle::class => ['all' => true],
];
```

## 使用方法

### 1. Repository 方式（主要使用场景）

Repository 是本 Bundle 的主要使用场景，支持注入 `ManagerRegistry` 来创建专用的数据访问层：

```php
<?php

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;

#[WithDedicatedEntityManager('order')]
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }
    
    public function findActiveOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }
    
    public function createOrder(array $data): Order
    {
        $order = new Order($data);
        $this->getEntityManager()->persist($order);
        $this->getEntityManager()->flush();
        
        return $order;
    }
}
```

在服务中使用 Repository：

```php
<?php

use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;

#[WithDedicatedEntityManager('order')]
class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository
    ) {}
    
    public function getActiveOrders(): array
    {
        return $this->orderRepository->findActiveOrders();
    }
    
    public function createOrder(array $data): Order
    {
        return $this->orderRepository->createOrder($data);
    }
}
```

#### 多通道 Repository 场景

不同的 Repository 可以使用不同的数据库通道：

```php
<?php

// 订单 Repository 使用订单数据库
#[WithDedicatedEntityManager('order')]
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }
}

// 用户 Repository 使用用户数据库
#[WithDedicatedEntityManager('user')]
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
}

// 日志 Repository 使用日志数据库
#[WithDedicatedEntityManager('log')]
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }
}
```

#### Repository 与 Service 的完整集成

```php
<?php

// Repository 层
#[WithDedicatedEntityManager('order')]
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }
    
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }
}

// Service 层 - 注入 Repository
#[WithDedicatedEntityManager('order')]
class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly UserRepository $userRepository  // 注入不同通道的 Repository
    ) {}
    
    public function processOrder(int $orderId, int $userId): void
    {
        // 使用订单数据库
        $order = $this->orderRepository->find($orderId);
        
        // 使用用户数据库
        $user = $this->userRepository->find($userId);
        
        // 业务逻辑处理...
    }
}
```

### 2. 直接注入 EntityManager 方式

```php
<?php

use Doctrine\ORM\EntityManagerInterface;
use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;

#[WithDedicatedEntityManager('order')]
class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}
    
    public function createOrder(array $data): Order
    {
        $order = new Order($data);
        $this->entityManager->persist($order);
        $this->entityManager->flush();
        
        return $order;
    }
}
```

### 3. 服务标签方式

```yaml
services:
    app.order_service:
        class: App\Service\OrderService
        tags:
            - { name: 'doctrine.dedicated_entity_manager', channel: 'order' }
```

### 4. 环境变量配置

为不同的通道配置独立的数据库连接：

```env
# 订单数据库配置
ORDER_DB_HOST=order-db.example.com
ORDER_DB_PORT=3306
ORDER_DB_NAME=orders
ORDER_DB_USER=order_user
ORDER_DB_PASSWORD=order_pass

# 用户数据库配置
USER_DB_HOST=user-db.example.com
USER_DB_PORT=3306
USER_DB_NAME=users
USER_DB_USER=user_user
USER_DB_PASSWORD=user_pass
```

## 工作原理

1. **注解扫描**: `WithDedicatedEntityManager` 注解通过 Symfony 的 `registerAttributeForAutoconfiguration` 自动添加服务标签
2. **连接创建**: 自动使用 `doctrine-dedicated-connection-bundle` 为每个通道创建专用的数据库连接  
3. **服务分析**: `EntityManagerChannelPass` 编译器通道扫描带有 `doctrine.dedicated_entity_manager` 标签的服务
4. **参数类型检测**: 自动检测服务构造函数参数类型：
   - `EntityManagerInterface` → 注入专用 EntityManager
   - `ManagerRegistry` → 注入专用 DedicatedManagerRegistry
5. **工厂服务注册**: 为每个通道创建专用的 EntityManager 和 ManagerRegistry 服务
6. **依赖注入**: 自动将相应的专用服务注入到目标服务的构造函数中

### Repository 工作流程

当 Repository 类使用 `#[WithDedicatedEntityManager('channel')]` 注解时：

1. **服务标签添加**: 注解自动为 Repository 添加 `doctrine.dedicated_entity_manager` 标签
2. **参数类型分析**: 编译器检测到构造函数需要 `ManagerRegistry` 参数
3. **专用 Registry 创建**: 为指定通道创建 `DedicatedManagerRegistry` 实例
4. **EntityManager 关联**: DedicatedManagerRegistry 通过 EntityManagerFactory 获取对应通道的 EntityManager
5. **Repository 实例化**: Symfony 容器使用专用 ManagerRegistry 实例化 Repository

这样，Repository 通过 `$this->getEntityManager()` 获取的就是指定通道的专用 EntityManager。

## 集成优势

与 `doctrine-dedicated-connection-bundle` 集成后，本 Bundle 自动享受以下能力：

- **统一连接管理**: 专用 EntityManager 和连接共享相同的配置和生命周期
- **环境变量配置**: 无需重复配置，直接使用连接 Bundle 的环境变量系统
- **协程支持**: 连接和 EntityManager 在协程环境中的一致性管理
- **资源优化**: 避免重复创建连接，提高资源利用效率

## 高级配置

### 协程环境支持

Bundle 自动检测协程环境并管理 EntityManager 生命周期：

```php
// 在协程环境中，每个协程都有独立的 EntityManager 实例
// 协程结束时自动清理资源
```

### 手动获取 EntityManager 和 ManagerRegistry

```php
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\DedicatedManagerRegistryFactory;

class SomeService
{
    public function __construct(
        private readonly EntityManagerFactory $emFactory,
        private readonly DedicatedManagerRegistryFactory $registryFactory
    ) {}
    
    public function doSomething(): void
    {
        // 直接获取 EntityManager
        $orderEntityManager = $this->emFactory->createEntityManager('order');
        $userEntityManager = $this->emFactory->createEntityManager('user');
        
        // 获取专用的 ManagerRegistry（适用于需要动态创建 Repository 的场景）
        $orderRegistry = $this->registryFactory->createRegistry('order');
        $userRegistry = $this->registryFactory->createRegistry('user');
        
        // 通过 ManagerRegistry 获取 EntityManager
        $orderEM = $orderRegistry->getManager();
        $userEM = $userRegistry->getManager();
    }
}
```

### 动态 Repository 创建

在某些场景下，你可能需要动态创建 Repository：

```php
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\DedicatedManagerRegistryFactory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class DynamicRepositoryService
{
    public function __construct(
        private readonly DedicatedManagerRegistryFactory $registryFactory
    ) {}
    
    public function createRepositoryForChannel(string $channel, string $entityClass): ServiceEntityRepository
    {
        $registry = $this->registryFactory->createRegistry($channel);
        
        return new class($registry, $entityClass) extends ServiceEntityRepository {
            public function __construct($registry, $entityClass)
            {
                parent::__construct($registry, $entityClass);
            }
        };
    }
    
    public function getOrderRepositoryForTenant(string $tenantId): ServiceEntityRepository
    {
        $channel = "tenant_{$tenantId}_orders";
        return $this->createRepositoryForChannel($channel, Order::class);
    }
}
```

### 获取对应的连接

```php
use Tourze\DoctrineDedicatedConnectionBundle\Factory\DedicatedConnectionFactory;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;

class DataMigrationService
{
    public function __construct(
        private readonly EntityManagerFactory $emFactory,
        private readonly DedicatedConnectionFactory $connectionFactory
    ) {}
    
    public function migrate(): void
    {
        // 获取专用连接和 EntityManager
        $connection = $this->connectionFactory->createConnection('migration');
        $entityManager = $this->emFactory->createEntityManager('migration');
        
        // 执行迁移操作
    }
}
```

## 测试

运行测试套件：

```bash
vendor/bin/phpunit
```

## 许可证

MIT 许可证。详情请参阅 [LICENSE](LICENSE) 文件。