<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineDedicatedConnectionBundle\DoctrineDedicatedConnectionBundle;
use Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection\Compiler\DedicatedEntityManagerCompilerPass;
use Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection\Compiler\EntityManagerChannelPass;
use Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection\DoctrineDedicatedEntityManagerExtension;

class DoctrineDedicatedEntityManagerBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            DoctrineDedicatedConnectionBundle::class => ['all' => true],
        ];
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // 注册编译器传递
        // EntityManagerChannelPass 必须在 AutowirePass 之后运行
        // AutowirePass 在 TYPE_BEFORE_OPTIMIZATION 阶段运行，所以我们在 TYPE_BEFORE_REMOVING 阶段运行
        $container->addCompilerPass(new EntityManagerChannelPass(), PassConfig::TYPE_BEFORE_REMOVING, 0);

        // DedicatedEntityManagerCompilerPass 处理手动标记的服务
        $container->addCompilerPass(new DedicatedEntityManagerCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING, 0);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new DoctrineDedicatedEntityManagerExtension();
        }

        return $this->extension !== false ? $this->extension : null;
    }
}
