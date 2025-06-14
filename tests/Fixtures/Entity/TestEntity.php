<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestEntityRepository::class)]
#[ORM\Table(name: 'test_entities')]
class TestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category = null;

    public function __construct(string $name, ?string $category = null)
    {
        $this->name = $name;
        $this->category = $category;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }
}