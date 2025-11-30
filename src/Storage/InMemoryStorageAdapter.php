<?php

namespace Acme\RateLimiter\Storage;

use Acme\RateLimiter\BucketState;

/**
 * In-memory storage adapter for bucket states.
 *
 * This implementation stores bucket data in a local array and is ideal for:
 *  - Unit testing
 *  - Single-process CLI workers
 *  - Simple applications not requiring persistence across requests
 *
 * Not suitable for:
 *  - Multi-worker environments (e.g., PHP-FPM)
 *  - Distributed systems
 *  - Long-lived persistence needs
 *
 * Buckets are stored under a composite ID:
 *     "{$resource}:{$key}"
 */
final class InMemoryStorageAdapter implements StorageAdapterInterface
{
    /**
     * @var array<string, BucketState> Associative array mapping ID → bucket state.
     */
    private array $buckets = [];

    /**
     * {@inheritdoc}
     */
    public function load(string $key, string $resource): ?BucketState
    {
        $id = $this->id($key, $resource);

        if (!isset($this->buckets[$id])) {
            return null;
        }

        return $this->buckets[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function save(string $key, string $resource, BucketState $state): void
    {
        $this->buckets[$this->id($key, $resource)] = $state;
    }

    /**
     * Builds a unique identifier for bucket storage.
     *
     * @param string $key
     * @param string $resource
     *
     * @return string
     */
    private function id(string $key, string $resource): string
    {
        return $resource . ':' . $key;
    }
}
