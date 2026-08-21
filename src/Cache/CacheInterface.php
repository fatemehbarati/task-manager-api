<?php

namespace Fatemeh\TaskManagerApi\Cache;

interface CacheInterface
{
    public function  get(string $key): ?string;
    public function set(string $key, string $value, int $ttlSeconds): void;
    public function  delete(string $key): void;
}
