<?php
namespace Fatemeh\TaskManagerApi\Cache;

use Redis;

class RedisCache implements CacheInterface {
    private Redis $redis;

    public function __construct()
    {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);

    }

    public function get(string $key): ?string
    {
        $value = $this->redis->get($key);
        if($value === false) {
            return null;
        }

        return $value;
    }

    public function set(string $key, string $value, int $ttlSeconds): void
    {
        $this->redis->setex($key, $ttlSeconds, $value);
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }
}