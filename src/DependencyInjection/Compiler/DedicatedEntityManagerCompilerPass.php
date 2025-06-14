<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;

/**
 * 处理手动标记的服务和 WithDedicatedEntityManager 注解
 */
class DedicatedEntityManagerCompilerPass implements CompilerPassInterface
{
    use EntityManagerCreationTrait;

    public function process(ContainerBuilder $container): void
    {
        // 处理带有 WithDedicatedEntityManager 注解的服务
        $this->processAttributedServices($container);
        
        // 处理手动标记的服务（向后兼容）
        $this->processTaggedServices($container);
    }

    private function processAttributedServices(ContainerBuilder $container): void
    {
        if (PHP_VERSION_ID < 80000) {
            return;
        }

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if (!$class || !$container->getReflectionClass($class, false)) {
                continue;
            }

            try {
                $reflectionClass = $container->getReflectionClass($class);
                $attributes = $reflectionClass->getAttributes(WithDedicatedEntityManager::class);
                
                foreach ($attributes as $attribute) {
                    /** @var WithDedicatedEntityManager $attributeInstance */
                    $attributeInstance = $attribute->newInstance();
                    
                    // 添加标签
                    $definition->addTag('doctrine.dedicated_entity_manager', [
                        'channel' => $attributeInstance->channel,
                    ]);
                }
            } catch (\ReflectionException $e) {
                // 忽略反射错误
            }
        }
    }

    private function processTaggedServices(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds('doctrine.dedicated_entity_manager');
        
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $channel = $attributes['channel'] ?? null;
                if ($channel) {
                    $this->ensureEntityManagerService($container, $channel, $attributes);
                }
            }
        }
    }
}