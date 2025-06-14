<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;

#[WithDedicatedEntityManager('mixed')]
class TestServiceWithConnection
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection
    ) {
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}