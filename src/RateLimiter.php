<?php

namespace Acme\RateLimiter;

use Acme\RateLimiter\Storage\StorageAdapterInterface;

/**
 * Core Rate Limiter implementation using the token bucket algorithm.
 *
 * This class provides the main `allow()` API used to check whether an action
 * is permitted under the configured rate limits. It relies on a storage backend
 * to persist bucket state across requests and computes refill rate at the
 * granularity of tokens-per-second, regardless of the original interval
 * specified during construction.
 */
final class RateLimiter implements RateLimiterInterface
{
    /**
     * Token bucket handler for refill and consumption logic.
     *
     * @var TokenBucket
     */
    private TokenBucket $bucket;

    /**
     * Number of tokens replenished per second.
     *
     * @var float
     */
    private float $refillRatePerSecond;

    /**
     * @param StorageAdapterInterface $storage            Storage backend used to persist bucket state.
     * @param int                     $capacity           Maximum number of tokens allowed in the bucket.
     * @param int                     $tokensPerInterval  Number of tokens regenerated every interval.
     * @param int                     $intervalInSeconds  Interval length in seconds (e.g., 1, 60, 3600).
     */
    public function __construct(
        private StorageAdapterInterface $storage,
        private int $capacity,
        int $tokensPerInterval,
        int $intervalInSeconds
    ) {
        $this->bucket = new TokenBucket();
        $this->refillRatePerSecond = $tokensPerInterval / $intervalInSeconds;
    }

    /**
     * Attempts to consume tokens for a given key + resource combination.
     *
     * If tokens are available, the method updates bucket state and returns true.
     * If not enough tokens are available, it returns false but still persists
     * the newly-refilled bucket state.
     *
     * @param string $key      Identifier for the rate-limited entity (e.g., user ID, IP).
     * @param string $resource The target resource or action being rate-limited.
     * @param int    $tokens   How many tokens are required for this action. Defaults to 1.
     *
     * @return bool True if allowed (tokens consumed), false otherwise.
     */
    public function allow(string $key, string $resource, int $tokens = 1): bool
    {
        $now = microtime(true);

        // Load or initialize bucket state
        $state = $this->storage->load($key, $resource);

        if ($state === null) {
            $state = BucketState::newFull(
                $this->capacity,
                $this->refillRatePerSecond,
                $now
            );
        }

        // Always refill first so we can save updated state even when denied
        $refilled = $this->bucket->refill($state, $now);

        // Attempt to consume tokens
        $updated = $this->bucket->consume($refilled, $tokens);

        if ($updated === null) {
            // Persist refilled state even when request is rejected
            $this->storage->save($key, $resource, $refilled);
            return false;
        }

        // Save the successful updated state
        $this->storage->save($key, $resource, $updated);

        return true;
    }

    /**
     * Returns the current bucket state for inspection/debugging.
     *
     * @param string $key      Identifier associated with the rate limit.
     * @param string $resource The resource/action being queried.
     *
     * @return BucketState|null The current state, or null if no state exists yet.
     */
    public function getState(string $key, string $resource): ?BucketState
    {
        return $this->storage->load($key, $resource);
    }
}
