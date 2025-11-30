<?php

namespace Acme\RateLimiter;

/**
 * Immutable value object representing the state of a token bucket.
 *
 * This DTO contains the bucket's capacity, refill rate, current token count,
 * and the timestamp of the last refill event. It is designed to be
 * fully immutable—any state change results in creation of a new instance.
 *
 * Used internally by the rate limiter to track consumption and refilling
 * across requests, often persisted through a storage backend.
 */
final class BucketState
{
    /**
     * @param int   $capacity            Maximum number of tokens the bucket can hold.
     * @param float $refillRatePerSecond Number of tokens replenished per second.
     * @param float $tokens              Current number of tokens available.
     * @param float $lastRefillTimestamp Timestamp (microtime(true)) of last refill.
     *
     * @throws \InvalidArgumentException When any argument violates expected constraints.
     */
    public function __construct(
        private readonly int $capacity,
        private readonly float $refillRatePerSecond,
        private readonly float $tokens,
        private readonly float $lastRefillTimestamp
    ) {
        if ($capacity <= 0) {
            throw new \InvalidArgumentException("capacity must be > 0");
        }
        if ($refillRatePerSecond <= 0) {
            throw new \InvalidArgumentException("refill rate must be > 0");
        }
        if ($tokens < 0 || $tokens > $capacity) {
            throw new \InvalidArgumentException("tokens must be between 0 and capacity");
        }
    }

    /**
     * Returns the maximum number of tokens the bucket can hold.
     *
     * @return int
     */
    public function capacity(): int
    {
        return $this->capacity;
    }

    /**
     * Returns the number of tokens replenished per second.
     *
     * @return float
     */
    public function refillRatePerSecond(): float
    {
        return $this->refillRatePerSecond;
    }

    /**
     * Returns the current number of available tokens.
     *
     * @return float
     */
    public function tokens(): float
    {
        return $this->tokens;
    }

    /**
     * Returns the timestamp of the last refill event.
     *
     * @return float Microtime (seconds with fractions)
     */
    public function lastRefillTimestamp(): float
    {
        return $this->lastRefillTimestamp;
    }

    /**
     * Creates a new bucket state initialized at full capacity.
     *
     * @param int   $capacity            Maximum bucket size.
     * @param float $refillRatePerSecond Tokens replenished per second.
     * @param float $now                 Initial timestamp (microtime(true)).
     *
     * @return self
     */
    public static function newFull(
        int $capacity,
        float $refillRatePerSecond,
        float $now
    ): self {
        return new self($capacity, $refillRatePerSecond, $capacity, $now);
    }

    /**
     * Returns a new immutable BucketState with updated tokens and timestamp.
     *
     * This method is used during refill or consumption operations to produce
     * a new state instance without mutating the original one.
     *
     * @param float $tokens              New token count.
     * @param float $lastRefillTimestamp Timestamp of the refill action.
     *
     * @return self
     */
    public function withTokensAndTimestamp(
        float $tokens,
        float $lastRefillTimestamp
    ): self {
        return new self(
            $this->capacity,
            $this->refillRatePerSecond,
            $tokens,
            $lastRefillTimestamp
        );
    }
}
