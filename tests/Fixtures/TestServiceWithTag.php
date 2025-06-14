<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures;

use Doctrine\ORM\EntityManagerInterface;

class TestServiceWithTag
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