<?php

namespace Tourze\DoctrineDedicatedEntityManagerBundle\Tests;

use Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface;

class MockContextService implements ContextServiceInterface
{
    private static int $counter = 0;
    private string $id;
    private array $deferred = [];

    public function __construct()
    {
        $this->id = 'test-context-' . ++self::$counter;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function supportCoroutine(): bool
    {
        return false;
    }

    public function defer(callable $callback): void
    {
        $this->deferred[] = $callback;
    }

    public function reset(): void
    {
        $this->executeDeferred();
    }

    public function executeDeferred(): void
    {
        foreach ($this->deferred as $callback) {
            $callback();
        }
        $this->deferred = [];
    }
}