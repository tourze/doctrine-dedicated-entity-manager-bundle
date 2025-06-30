<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\DoctrineDedicatedEntityManagerBundle\Exception\InvalidArgumentException;

/**
 * 处理 doctrine.dedicated_entity_manager 标签的编译器传递
 * 这个 Pass 必须在 AutowirePass 之后运行，以便正确处理参数
 */
class EntityManagerChannelPass implements CompilerPassInterface
{
    use EntityManagerCreationTrait;

    public function process(ContainerBuilder $container): void
    {
        // 遍历所有服务定义来查找带有标签的服务，避免使用 findTaggedServiceIds
        foreach ($container->getDefinitions() as $id => $definition) {
            if (!$definition->hasTag('doctrine.dedicated_entity_manager')) {
                continue;
            }

            $tags = $definition->getTag('doctrine.dedicated_entity_manager');
            foreach ($tags as $attributes) {
                if (!is_array($attributes)) {
                    continue;
                }
                $channel = $attributes['channel'] ?? null;
                if (!is_string($channel)) {
                    throw new InvalidArgumentException(sprintf(
                        'Service "%s" has a "doctrine.dedicated_entity_manager" tag without a "channel" attribute.',
                        $id
                    ));
                }

                // 确保 EntityManager 服务存在
                $this->ensureEntityManagerService($container, $channel);

                // 获取服务 ID
                $entityManagerServiceId = sprintf('doctrine.orm.%s_entity_manager', $channel);
                $registryServiceId = sprintf('doctrine.dedicated_registry.%s', $channel);

                // 处理服务的参数注入
                $this->processServiceArguments($container, $definition, $entityManagerServiceId, $registryServiceId);
            }
        }
    }

    /**
     * 处理服务的参数注入（EntityManager 和 ManagerRegistry）
     */
    private function processServiceArguments(ContainerBuilder $container, Definition $definition, string $entityManagerServiceId, string $registryServiceId): void
    {
        $class = $definition->getClass();
        $reflectionClass = $container->getReflectionClass($class, false);
        if (null === $class || null === $reflectionClass) {
            return;
        }

        try {
            $constructor = $reflectionClass->getConstructor();

            if (null === $constructor) {
                return;
            }

            // 处理每个构造函数参数
            foreach ($constructor->getParameters() as $index => $parameter) {
                $type = $parameter->getType();

                if (!$type instanceof \ReflectionNamedType) {
                    continue;
                }

                $typeName = $type->getName();

                // 处理 EntityManagerInterface 类型参数
                if ($typeName === EntityManagerInterface::class ||
                    $typeName === 'Doctrine\ORM\EntityManager') {
                    $definition->setArgument($index, new Reference($entityManagerServiceId));
                    continue;
                }

                // 处理 ManagerRegistry 类型参数
                if ($typeName === ManagerRegistry::class) {
                    $definition->setArgument($index, new Reference($registryServiceId));
                    continue;
                }
            }
        } catch (\ReflectionException $e) {
            // 忽略反射错误
        }
    }
}
