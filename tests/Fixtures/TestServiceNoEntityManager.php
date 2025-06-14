<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures;

use Tourze\DoctrineDedicatedEntityManagerBundle\Attribute\WithDedicatedEntityManager;

#[WithDedicatedEntityManager('test')]
class TestServiceNoEntityManager
{
    public function __construct(
        private readonly string $someParameter = 'default'
    ) {
    }

    public function getSomeParameter(): string
    {
        return $this->someParameter;
    }
}