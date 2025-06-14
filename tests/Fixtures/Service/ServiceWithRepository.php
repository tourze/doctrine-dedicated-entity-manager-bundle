<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\Service;

use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\Entity\TestEntity;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\Repository\TestEntityRepository;

#[WithDedicatedEntityManager('service_repo')]
class ServiceWithRepository
{
    public function __construct(
        private readonly TestEntityRepository $repository
    ) {
    }

    /**
     * 创建测试实体
     */
    public function createTestEntity(string $name, ?string $category = null): TestEntity
    {
        $entity = new TestEntity($name, $category);
        
        $em = $this->repository->getEntityManager();
        $em->persist($entity);
        $em->flush();
        
        return $entity;
    }

    /**
     * 获取 Repository
     */
    public function getRepository(): TestEntityRepository
    {
        return $this->repository;
    }

    /**
     * 通过名称查找实体
     */
    public function findByName(string $name): ?TestEntity
    {
        return $this->repository->findByName($name);
    }

    /**
     * 获取所有分类
     */
    public function getAllCategories(): array
    {
        return $this->repository->getAllCategories();
    }
}