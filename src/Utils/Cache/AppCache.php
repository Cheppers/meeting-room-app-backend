<?php

namespace App\Utils\Cache;

use DateInterval;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class AppCache extends AbstractCache
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(AdapterInterface $cache, LoggerInterface $logger)
    {
        parent::__construct($cache);
        $this->logger = $logger;
    }

    public function getItem(string $cacheKey)
    {
        return $this->cache->getItem($cacheKey);
    }

    public function save(string $cacheKey, $value, string $expiresAfter = null)
    {
        if (is_callable($value)) {
            $value = call_user_func($value);
        }

        $item = $this->getItem($cacheKey);

        $item->set($value);
        if ($expiresAfter) {
            $item->expiresAfter(new DateInterval($expiresAfter));
        }
        $this->cache->save($item);

        return $item;
    }

    public function saveIfNotExists(string $cacheKey, $value, string $expiresAfter = null)
    {
        $item = $this->getItem($cacheKey);

        if ($item->isHit()) {
            return $item;
        }

        return $this->save($cacheKey, $value, $expiresAfter);
    }

    public function clear(): void
    {
        $this->logger->info('CACHE_CLEAR');
        $this->cache->clear();
    }
}
