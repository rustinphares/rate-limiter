<?php

namespace Acme\RateLimiter;

/**
 * Token bucket algorithm implementation.
 *
 * This class performs the core mechanics of:
 *  - refilling tokens based on elapsed time
 *  - consuming tokens when available
 *
 * The bucket state itself is stored in an immutable {@see BucketState} object.
 * Each operation returns a *new* BucketState instance without modifying the
 * original, ensuring thread-safety and predictable behavior in distributed
 * environments (provided the storage layer handles concurrency).
 */
final class TokenBucket
{
    /**
     * Refills the bucket based on elapsed time since the last refill.
     *
     * The refill rate is defined as tokens-per-second, stored inside the
     * BucketState. Elapsed time is calculated using the difference between the
     * provided timestamp and the bucket’s lastRefillTimestamp.
     *
     * Float precision drift is minimized by rounding token values to 6 decimals.
     *
     * @param BucketState $state Current bucket state.
     * @param float       $now   Current timestamp (microtime(true)).
     *
     * @return BucketState New bucket state after refill.
     */
    public function refill(BucketState $state, float $now): BucketState
    {
        $last = $state->lastRefillTimestamp();
        $available = $state->tokens();

        // No time has passed — nothing to refill
        if ($now <= $last) {
            return $state;
        }

        // Compute elapsed seconds and tokens to add
        $elapsed = $now - $last;
        $added = $elapsed * $state->refillRatePerSecond();

        $available += $added;

        // Avoid floating-point accumulation errors
        $available = round($available, 6);

        // Cap at bucket capacity
        $available = min($available, $state->capacity());

        return $state->withTokensAndTimestamp($available, $now);
    }

    /**
     * Attempts to consume tokens from the bucket.
     *
     * If enough tokens are available, returns a new BucketState instance with
     * updated token count. If not enough tokens are available, returns null
     * and the caller should interpret this as "rate limit exceeded".
     *
     * Note: The timestamp is *not* updated on consumption, only on refill.
     *
     * @param BucketState $state  Current bucket state.
     * @param int         $tokens Number of tokens to consume.
     *
     * @return BucketState|null New bucket state, or null if insufficient tokens.
     */
    public function consume(BucketState $state, int $tokens): ?BucketState
    {
        $available = $state->tokens();

        if ($available < $tokens) {
            return null;
        }

        $remaining = $available - $tokens;

        // Normalize floating number after subtraction
        $remaining = round($remaining, 6);

        return $state->withTokensAndTimestamp(
            $remaining,
            $state->lastRefillTimestamp()
        );
    }
}
