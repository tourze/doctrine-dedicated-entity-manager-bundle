<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;
use Tourze\DoctrineDedicatedEntityManagerBundle\Factory\EntityManagerFactory;

class DoctrineDedicatedEntityManagerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');
        
        // Ensure the factory is available for compiler passes
        $container->setAlias(EntityManagerFactory::class, 'doctrine_dedicated_entity_manager.factory')
            ->setPublic(false);
        
        // Register attribute autoconfiguration
        if (PHP_VERSION_ID >= 80000) {
            $this->registerAttributeAutoconfiguration($container);
        }
    }
    
    private function registerAttributeAutoconfiguration(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            WithDedicatedEntityManager::class,
            static function (ChildDefinition $definition, WithDedicatedEntityManager $attribute): void {
                $definition->addTag('doctrine.dedicated_entity_manager', [
                    'channel' => $attribute->channel,
                ]);
            }
        );
    }

    public function getAlias(): string
    {
        return 'doctrine_dedicated_entity_manager';
    }
}
