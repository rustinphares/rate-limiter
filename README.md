## DISCLAIMER

This repository is experimental for educational purposes only.

# Acme Rate Limiter

A lightweight, extensible, and framework-agnostic **Token Bucket rate limiter** for PHP.  
Supports custom storage backends (Redis, database, in-memory, etc.), high-precision refill timing, and clean functional state transitions.

This library is designed for API limiting, user-level quotas, job throttling, and distributed rate limiting when paired with a shared storage adapter.

---

## 🚀 Features

- **Token Bucket algorithm** (predictable, smooth limiting)
- **High-resolution timing** using `microtime(true)`
- **Extensible storage layer**
  - In-memory (included)
  - Redis / Memcached / SQL (easy to add)
- **Stateless logic layer**  
  (`TokenBucket` contains pure functions; all state is external)
- **No framework dependencies**
- **PSR-12 and PHPDoc compliant**
- **Testable and deterministic**

---

## 📦 Installation

```bash
composer require acme/rate-limiter
```
---

## 🧠 How It Works (In Simple Terms)

Each (resource, key) pair gets a bucket:

- **capacity** – maximum tokens allowed
- **refillRatePerSecond** – how fast new tokens are added
- **tokens** – current available tokens
- **lastRefillTimestamp** – last time tokens were refilled

Every time you call allow():

1. Load state from storage
2. Refill tokens based on elapsed time
3. Attempt to consume tokens
4. Save the new state
5. Return true or false

This architecture guarantees deterministic behavior without holding logic in memory—only data.

---

## 🧩 Basic Usage

```php
use Acme\RateLimiter\RateLimiterFactory;
use Acme\RateLimiter\Storage\InMemoryStorageAdapter;

$storage = new InMemoryStorageAdapter();

$limiter = RateLimiterFactory::perSecond(
    storage: $storage,
    capacity: 10,
    tokensPerSecond: 5
);

if ($limiter->allow('user123', 'login')) {
    echo "Allowed!";
} else {
    echo "Too many requests.";
}
```

---

## ⚙️ Configuration Options

**perSecond(Storage, capacity, tokensPerSecond)**
Fastest granularity, good for:
- real-time APIs
- chat rate limits

**perMinute(Storage, capacity, tokensPerMinute)**
Good for:
- authentication endpoints
- form submissions

**perHour(Storage, capacity, tokensPerHour)**
Good for:
- batch jobs
- scheduled or background tasks

---

## 🏗 Architecture Diagram
```text
                         ┌──────────────────────────┐
                         │      RateLimiter         │
                         │--------------------------│
                         │ - allow()                │
                         │ - getState()             │
                         └──────────┬───────────────┘
                                    │
                                    │ loads/saves state
                                    ▼
                    ┌────────────────────────────────────┐
                    │       StorageAdapterInterface      │
                    │------------------------------------│
                    │ + load(key, resource): ?BucketState│
                    │ + save(key, resource, BucketState) │
                    └───────────┬───────────┬───────────┘
                                │           │
                                │           │
           ┌────────────────────┘       ┌──────────────────────┐
           │                            │                      │
┌──────────────────────────┐   ┌───────────────────────┐   ┌──────────────────────────┐
│ InMemoryStorageAdapter   │   │ RedisStorageAdapter*   │   │  DatabaseStorageAdapter* │
│--------------------------│   │  (future implementation│   │  (future implementation) │
│ stores state in PHP array│   │   using atomic LUA)   │   │   SQL/NoSQL persistence  │
└─────────────┬────────────┘   └──────────────┬────────┘   └──────────────┬──────────┘
              │                               │                           │
              │                               │                           │
              ▼                               ▼                           ▼
    ┌────────────────────────┐       ┌────────────────────┐      ┌───────────────────┐
    │       BucketState      │       │   Serialized State  │      │   Persisted State │
    │------------------------│       │   (Redis/Memcached) │      │ (DB row/document) │
    │ tokens                 │       └────────────────────┘      └───────────────────┘
    │ lastRefillTimestamp   │
    │ capacity              │
    │ refillRatePerSecond   │
    └─────────┬────────────┘
              │
              │ passed into
              ▼
       ┌───────────────────────┐
       │      TokenBucket      │
       │-----------------------│
       │ + refill()            │
       │ + consume()           │
       │  (pure functions)     │
       └───────────────────────┘
```

---

## 🧬 Bucket State Evolution Example
```text
capacity = 10
refill = 5 tokens/sec
------------------------------------
t=0.0   tokens=10
t=0.2   tokens=11 (clamped to 10)
consume 3 → tokens=7
t=1.2   refill +5 → tokens=10
consume 11 → fails
```
---

## 🛠 Extending the Storage Layer

To add a custom backend (Redis, SQL, etc.), implement:
```php
interface StorageAdapterInterface
{
    public function load(string $key, string $resource): ?BucketState;

    public function save(string $key, string $resource, BucketState $state): void;
}
```
Then plug your adapter into any factory method.

---

## 🧪 Testing

You can test the refill logic deterministically by providing explicit timestamps:
```php
$bucket = new TokenBucket();

$state = new BucketState(
    capacity: 10,
    refillRatePerSecond: 10,
    tokens: 0,
    lastRefillTimestamp: 0
);

$state = $bucket->refill($state, 1.0);
// tokens = 10
```
Because the logic is stateless and pure, it is straightforward to unit test.

---

## 📄 License

MIT License — free for commercial and private use.
