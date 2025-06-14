<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;

#[WithDedicatedEntityManager('test')]
class TestService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}