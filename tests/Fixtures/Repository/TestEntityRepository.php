<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;
use Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\Entity\TestEntity;

#[WithDedicatedEntityManager('test_repo')]
class TestEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestEntity::class);
    }

    /**
     * 根据名称查找实体
     */
    public function findByName(string $name): ?TestEntity
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * 根据分类查找实体列表
     */
    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category]);
    }

    /**
     * 创建自定义查询
     */
    public function findByNameLike(string $pattern): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.name LIKE :pattern')
            ->setParameter('pattern', '%' . $pattern . '%')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计某个分类的实体数量
     */
    public function countByCategory(string $category): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 获取所有分类
     */
    public function getAllCategories(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('DISTINCT t.category')
            ->where('t.category IS NOT NULL')
            ->orderBy('t.category', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'category');
    }
}