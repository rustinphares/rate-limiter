<?php

namespace Acme\RateLimiter;

/**
 * Contract for a token-bucket-based rate limiter.
 *
 * Implementations of this interface must provide:
 *  - A mechanism to check whether an action is allowed (`allow`)
 *  - A way to retrieve the current bucket state for inspection or debugging (`getState`)
 *
 * The interface intentionally does not dictate the storage mechanism;
 * different adapters (in-memory, Redis, database, file-based, etc.)
 * can be used behind the scenes.
 */
interface RateLimiterInterface
{
    /**
     * Attempts to consume tokens for a given key/resource combination.
     *
     * The implementation should:
     *  - Refill the bucket based on elapsed time
     *  - Attempt to consume the given number of tokens
     *  - Persist the updated bucket state
     *  - Return true if the requested tokens were successfully consumed
     *  - Return false if insufficient tokens are available
     *
     * @param string $key      Identifier for the entity being rate-limited (e.g., user ID, IP).
     * @param string $resource The resource or action being rate-limited.
     * @param int    $tokens   Number of tokens required for this request. Defaults to 1.
     *
     * @return bool True if the action is allowed (tokens consumed), false otherwise.
     */
    public function allow(string $key, string $resource, int $tokens = 1): bool;

    /**
     * Returns the current bucket state for a given key/resource pair.
     *
     * Implementations should return null when the bucket has not yet
     * been initialized, which typically means the caller has never made
     * a request against the rate limiter for this key/resource.
     *
     * @param string $key      Entity identifier associated with the rate limit.
     * @param string $resource Name of the resource/action being queried.
     *
     * @return BucketState|null The current bucket state, or null if none exists.
     */
    public function getState(string $key, string $resource): ?BucketState;
}
