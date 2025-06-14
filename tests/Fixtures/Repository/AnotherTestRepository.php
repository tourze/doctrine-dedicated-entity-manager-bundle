<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\Entity\TestEntity;

#[WithDedicatedEntityManager('another_repo')]
class AnotherTestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestEntity::class);
    }

    /**
     * 简单的查找方法
     */
    public function findAll(): array
    {
        return parent::findAll();
    }

    /**
     * 测试不同通道返回不同的 EntityManager
     */
    public function getEntityManagerClass(): string
    {
        return get_class($this->getEntityManager());
    }

    /**
     * 获取注册表信息
     */
    public function getRegistryInfo(): array
    {
        $registry = $this->_em->getConfiguration();
        return [
            'entity_manager' => get_class($this->getEntityManager()),
            'connection' => get_class($this->getEntityManager()->getConnection()),
            'configuration' => get_class($registry),
        ];
    }
}