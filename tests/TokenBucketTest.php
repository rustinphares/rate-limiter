<?php

namespace Acme\RateLimiter\Tests;

use Acme\RateLimiter\BucketState;
use Acme\RateLimiter\TokenBucket;
use PHPUnit\Framework\TestCase;

final class TokenBucketTest extends TestCase
{
    public function testConsumesWhenEnoughTokens(): void
    {
        $bucket = new TokenBucket();
        $state = new BucketState(10, 1.0, 10, 0.0);

        $updated = $bucket->consume($state, 5);

        self::assertNotNull($updated);
        self::assertSame(5.0, $updated->tokens());
    }

    public function testDeniesWhenNotEnoughTokens(): void
    {
        $bucket = new TokenBucket();
        $state = new BucketState(10, 1.0, 1.0, 0.0);

        $updated = $bucket->consume($state, 5);

        self::assertNull($updated);
    }
}
