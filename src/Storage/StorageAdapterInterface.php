<?php

namespace Acme\RateLimiter\Storage;

use Acme\RateLimiter\BucketState;

/**
 * Storage abstraction for persisting rate limiter bucket state.
 *
 * Implementations define how bucket states are stored and retrieved.
 * Examples include:
 *  - In-memory storage (non-persistent, per-process)
 *  - Redis or Memcached
 *  - SQL/NoSQL databases
 *  - File-based storage
 *
 * The interface does not impose atomicity requirements—however,
 * distributed storage implementations *should* ensure that
 * load-modify-save cycles are safe under concurrency.
 */
interface StorageAdapterInterface
{
    /**
     * Load the bucket state for the given key/resource.
     *
     * Implementations should return null if no bucket exists,
     * which typically indicates that the rate limiter has never been
     * invoked for this key/resource pair.
     *
     * @param string $key      Unique identifier for the rate-limited entity.
     * @param string $resource Name of the specific resource/action.
     *
     * @return BucketState|null The stored state or null if not found.
     */
    public function load(string $key, string $resource): ?BucketState;

    /**
     * Persist the bucket state for the given key/resource.
     *
     * If the bucket already exists, it should overwrite it.
     * Storage layers may serialize, encode, or transform the data.
     *
     * @param string      $key      Identifier for the entity.
     * @param string      $resource Resource/action name.
     * @param BucketState $state    The current state of the bucket.
     */
    public function save(string $key, string $resource, BucketState $state): void;
}
