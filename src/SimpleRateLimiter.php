<?php

namespace Acme\RateLimiter;

final class SimpleRateLimiter
{
    public function __construct(
        private RateLimiterInterface $limiter,
        private string $resource = 'global'
    ) {
    }

    public function allow(string $key, int $tokens = 1): bool
    {
        return $this->limiter->allow($key, $this->resource, $tokens);
    }
}
