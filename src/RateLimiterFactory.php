<?php

namespace Acme\RateLimiter;

use Acme\RateLimiter\Storage\StorageAdapterInterface;

/**
 * Factory for creating RateLimiter instances with time-based refill intervals.
 *
 * This class provides convenience constructors for common rate-limiting
 * time windows (per second, per minute, per hour), translating human-friendly
 * intervals into refill periods used by RateLimiter.
 */
final class RateLimiterFactory
{
    /**
     * Create a rate limiter with a per-second refill window.
     *
     * @param StorageAdapterInterface $storage         Storage backend used to persist bucket state.
     * @param int                     $capacity        Maximum number of tokens in the bucket.
     * @param int                     $tokensPerSecond Number of tokens replenished each second.
     *
     * @return RateLimiter
     */
    public static function perSecond(
        StorageAdapterInterface $storage,
        int $capacity,
        int $tokensPerSecond
    ): RateLimiter {
        return new RateLimiter($storage, $capacity, $tokensPerSecond, 1);
    }

    /**
     * Create a rate limiter with a per-minute refill window.
     *
     * @param StorageAdapterInterface $storage         Storage backend used to persist bucket state.
     * @param int                     $capacity        Maximum number of tokens in the bucket.
     * @param int                     $tokensPerMinute Number of tokens replenished each minute.
     *
     * @return RateLimiter
     */
    public static function perMinute(
        StorageAdapterInterface $storage,
        int $capacity,
        int $tokensPerMinute
    ): RateLimiter {
        return new RateLimiter($storage, $capacity, $tokensPerMinute, 60);
    }

    /**
     * Create a rate limiter with a per-hour refill window.
     *
     * @param StorageAdapterInterface $storage       Storage backend used to persist bucket state.
     * @param int                     $capacity      Maximum number of tokens in the bucket.
     * @param int                     $tokensPerHour Number of tokens replenished each hour.
     *
     * @return RateLimiter
     */
    public static function perHour(
        StorageAdapterInterface $storage,
        int $capacity,
        int $tokensPerHour
    ): RateLimiter {
        return new RateLimiter($storage, $capacity, $tokensPerHour, 3600);
    }
}
