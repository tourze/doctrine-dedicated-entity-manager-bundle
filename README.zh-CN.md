# Doctrine 专用 EntityManager Bundle

专为 Symfony 应用设计的 Doctrine EntityManager 自动化管理包，通过注解和编译器通道实现多数据库 EntityManager 的自动创建和依赖注入。

## 特性

- ✨ **注解驱动**: 使用 `#[WithDedicatedEntityManager]` 注解实现零配置
- 🚀 **自动注册**: 编译器通道自动创建和配置 EntityManager 服务
- 🔗 **连接集成**: 与 `doctrine-dedicated-connection-bundle` 深度集成，自动创建对应连接
- 🔧 **环境变量**: 支持通过环境变量灵活配置多数据库连接
- ⚡ **协程友好**: 原生支持协程环境，自动管理资源生命周期
- 🔄 **向后兼容**: 同时支持手动标签配置方式

## 安装

```bash
composer require tourze/doctrine-dedicated-entity-manager-bundle
```

## 快速开始

### 1. 注册 Bundle

在 `config/bundles.php` 中添加：

```php
return [
    // ...
    Tourze\DoctrineDedicatedConnectionBundle\DoctrineDedicatedConnectionBundle::class => ['all' => true],
    Tourze\DoctrineDedicatedEntityManagerBundle\DoctrineDedicatedEntityManagerBundle::class => ['all' => true],
];
```

### 2. 使用注解

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
    
    public function processOrder(Order $order): void
    {
        // 自动使用 order 通道的专用 EntityManager
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }
}
```

### 3. 环境配置

设置不同通道的数据库连接：

```env
# 订单系统数据库
ORDER_DB_HOST=order-db.company.com
ORDER_DB_NAME=orders_production
ORDER_DB_USER=order_app
ORDER_DB_PASSWORD=secure_password

# 日志系统数据库
LOG_DB_HOST=log-db.company.com
LOG_DB_NAME=application_logs
LOG_DB_USER=log_reader
LOG_DB_PASSWORD=log_password
```

## 高级用法

### 服务标签方式

对于需要手动控制的场景：

```yaml
services:
    app.reporting_service:
        class: App\Service\ReportingService
        tags:
            - { name: 'doctrine.dedicated_entity_manager', channel: 'reporting' }
```

### 工厂模式

直接使用工厂获取 EntityManager：

```php
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;

class DataMigrationService
{
    public function __construct(
        private readonly EntityManagerFactory $factory
    ) {}
    
    public function migrate(): void
    {
        $sourceEM = $this->factory->createEntityManager('legacy');
        $targetEM = $this->factory->createEntityManager('modern');
        
        // 执行数据迁移逻辑
    }
}
```

## 配置说明

### 环境变量模式

每个通道支持以下环境变量（以 `ORDER` 通道为例）：

- `ORDER_DB_HOST` - 数据库主机
- `ORDER_DB_PORT` - 数据库端口
- `ORDER_DB_NAME` - 数据库名称
- `ORDER_DB_USER` - 用户名
- `ORDER_DB_PASSWORD` - 密码
- `ORDER_DB_DRIVER` - 驱动类型
- `ORDER_DB_CHARSET` - 字符集
- `ORDER_DB_SERVER_VERSION` - 服务器版本

### 默认行为

- 未配置的参数将继承默认 EntityManager 的配置
- 未指定数据库名时，使用 `默认数据库名_通道名` 格式

## 最佳实践

### 1. 通道命名

使用有意义的通道名称：

```php
#[WithDedicatedEntityManager('user_profile')]     // ✅ 清晰明确
#[WithDedicatedEntityManager('analytics')]        // ✅ 业务相关
#[WithDedicatedEntityManager('temp')]             // ❌ 含义不明
```

### 2. 服务分离

按业务领域分离服务：

```php
#[WithDedicatedEntityManager('order')]
class OrderService { /* 订单相关操作 */ }

#[WithDedicatedEntityManager('user')]
class UserService { /* 用户相关操作 */ }

#[WithDedicatedEntityManager('product')]
class ProductService { /* 商品相关操作 */ }
```

### 3. 测试环境

测试时使用内存数据库：

```env
# .env.test
ORDER_DB_DRIVER=pdo_sqlite
ORDER_DB_PATH=:memory:
```

## 故障排除

### 常见问题

1. **EntityManager 未注入**
   - 检查注解拼写
   - 确认 Bundle 已正确注册
   - 验证服务是否启用了 autowiring

2. **数据库连接失败**
   - 检查环境变量配置
   - 验证数据库服务器可访问性
   - 确认用户权限设置

3. **协程环境异常**
   - 确保安装了 `tourze/symfony-runtime-context-bundle`
   - 检查协程框架兼容性

### 调试模式

启用调试日志：

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['doctrine_entity_manager']
    handlers:
        doctrine_entity_manager:
            type: stream
            path: "%kernel.logs_dir%/entity_manager.log"
            channels: ['doctrine_entity_manager']
```

## 技术原理

1. **注解处理**: 利用 Symfony 的 `registerAttributeForAutoconfiguration` 自动标记服务
2. **编译时处理**: `EntityManagerChannelPass` 在容器编译阶段创建专用 EntityManager
3. **工厂模式**: `EntityManagerFactory` 管理多个 EntityManager 实例的生命周期
4. **环境感知**: 根据运行环境（协程/传统）调整资源管理策略

## 参考文档

- [Symfony Doctrine 配置](https://symfony.com/doc/current/doctrine.html)
- [Doctrine ORM 文档](https://www.doctrine-project.org/projects/orm.html)
- [PHP 8 注解特性](https://www.php.net/manual/en/language.attributes.php)
