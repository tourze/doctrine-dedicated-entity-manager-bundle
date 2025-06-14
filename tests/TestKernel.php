<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Tourze\DoctrineDedicatedConnectionBundle\DoctrineDedicatedConnectionBundle;
use Tourze\DoctrineDedicatedEntityManagerBundle\DoctrineDedicatedEntityManagerBundle;
use Tourze\Symfony\RuntimeContextBundle\RuntimeContextBundle;

class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new RuntimeContextBundle(),
            new DoctrineDedicatedConnectionBundle(),
            new DoctrineDedicatedEntityManagerBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->setParameter('kernel.secret', 'test');
            $container->setParameter('kernel.charset', 'UTF-8');
            
            // Framework configuration
            $container->loadFromExtension('framework', [
                'test' => true,
                'router' => [
                    'utf8' => true,
                    'resource' => __DIR__ . '/routes.yaml',
                ],
                'secret' => 'test',
                'handle_all_throwables' => true,
                'http_method_override' => false,
                'trusted_hosts' => null,
                'trusted_proxies' => null,
                'php_errors' => [
                    'log' => true,
                ],
            ]);

            // Doctrine configuration
            $container->loadFromExtension('doctrine', [
                'dbal' => [
                    'driver' => 'pdo_sqlite',
                    'path' => ':memory:',
                    'charset' => 'utf8',
                ],
                'orm' => [
                    'auto_generate_proxy_classes' => true,
                    'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                    'auto_mapping' => true,
                ],
            ]);

            // Mock ContextServiceInterface for testing
            $container->register('test.context_service', MockContextService::class);
            $container->setAlias('Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface', 'test.context_service');
            
            // Mock logger
            $container->register('logger', NullLogger::class);
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/doctrine_dedicated_entity_manager_bundle_tests/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/doctrine_dedicated_entity_manager_bundle_tests/logs';
    }
}