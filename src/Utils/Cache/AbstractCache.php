<?php

namespace App\Utils\Cache;

abstract class AbstractCache
{
    protected $cache;

    public function __construct($cache)
    {
        $this->cache = $cache;
    }

    abstract public function getItem(string $cacheKey);

    abstract public function save(string $cacheKey, $value, string $expiresAfter = null);

    abstract public function saveIfNotExists(string $cacheKey, $value, string $expiresAfter = null);

    abstract public function clear(): void;
}
