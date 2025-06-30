<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection\Compiler\DedicatedEntityManagerCompilerPass;
use Tourze\DoctrineDedicatedEntityManagerBundle\DependencyInjection\Compiler\EntityManagerChannelPass;
use Tourze\DoctrineDedicatedEntityManagerBundle\DoctrineDedicatedEntityManagerBundle;

class DoctrineDedicatedEntityManagerBundleTest extends TestCase
{
    private DoctrineDedicatedEntityManagerBundle $bundle;

    protected function setUp(): void
    {
        $this->bundle = new DoctrineDedicatedEntityManagerBundle();
    }

    public function testBuild(): void
    {
        $container = new ContainerBuilder();
        
        $this->bundle->build($container);

        // 验证编译器传递是否被添加
        $compilerPasses = $container->getCompilerPassConfig()->getPasses();
        
        // 查找我们的编译器传递
        $foundDedicatedEntityManagerPass = false;
        $foundEntityManagerChannelPass = false;
        
        foreach ($compilerPasses as $pass) {
            if ($pass instanceof DedicatedEntityManagerCompilerPass) {
                $foundDedicatedEntityManagerPass = true;
            }
            if ($pass instanceof EntityManagerChannelPass) {
                $foundEntityManagerChannelPass = true;
            }
        }
        
        $this->assertTrue($foundDedicatedEntityManagerPass, 'DedicatedEntityManagerCompilerPass should be registered');
        $this->assertTrue($foundEntityManagerChannelPass, 'EntityManagerChannelPass should be registered');
    }

    public function testGetContainerExtension(): void
    {
        $extension = $this->bundle->getContainerExtension();
        
        $this->assertNotNull($extension);
        $this->assertEquals('doctrine_dedicated_entity_manager', $extension->getAlias());
    }

    public function testBundleInheritance(): void
    {
        $this->assertInstanceOf(\Symfony\Component\HttpKernel\Bundle\Bundle::class, $this->bundle);
    }
}