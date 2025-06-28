<?php
/**
 * Copyright © 2025 MageStack. All rights reserved.
 * See COPYING.txt for license details.
 *
 * DISCLAIMER
 *
 * Do not make any kind of changes to this file if you
 * wish to upgrade this extension to newer version in the future.
 *
 * @category  MageStack
 * @package   MageStack_ParsistentCache
 * @author    Amit Biswas <amit.biswas.webdeveloper@gmail.com>
 * @copyright 2025 MageStack
 * @license   https://opensource.org/licenses/MIT  MIT License
 * @link      https://github.com/attherateof/ParsistentCache
 */

declare(strict_types=1);

namespace MageStack\ParsistentCache\Model;

use MageStack\ParsistentCache\Api\CacheRepositoryInterface;
use Magento\Framework\App\Cache\Frontend\Pool as CacheFrontendPool;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Psr\Log\LoggerInterface;
use Zend_Cache;
use RuntimeException;

/**
 * Persistance cache repository
 *
 * class CacheRepository
 * namespace MageStack\ParsistentCache\Model
 *
 * @api
 *
 */
class CacheRepository implements CacheRepositoryInterface
{
    /**
     * Cache constants
     */
    private const CACHE_IDENTIFIER = 'parsistent';
    private const CACHE_PREFIX = 'parsistent_';
    private const DEFAULT_LIFETIME = 60 * 60; // 1 hour
    private const CACHE_TAG = 'parsistent';

    /**
     * Persistance cache object
     *
     * @var null|FrontendInterface
     */
    private ?FrontendInterface $cache = null;

    /**
     * @param CacheFrontendPool $cacheFrontendPool
     * @param EncryptorInterface $encryptor
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CacheFrontendPool $cacheFrontendPool,
        private readonly EncryptorInterface $encryptor,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(
        string $key,
        string $data,
        array $tags = [],
        ?int $lifetime = null
    ): void {
        try {
            $cacheKey = $this->generateCacheKey($key);
            $cacheTags = array_merge([self::CACHE_TAG], $tags);
            $cacheLifetime = $lifetime ?? self::DEFAULT_LIFETIME;
            $this->getCache()->save(
                $data,
                $cacheKey,
                $cacheTags,
                $cacheLifetime
            );
        } catch (\Throwable $th) {
            $this->logger->error('[MageStack][StatelessGuestValidator] Error while saving cache.', [
                'key' => $key,
                'message' => $th->getMessage(),
                'stack_trace' => $th->getTraceAsString(),
            ]);

            throw new RuntimeException("Unable to save data to persistance cache.");
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): ?string
    {
        try {
            $cacheKey = $this->generateCacheKey($key);
            $cachedData = $this->getCache()->load($cacheKey);

            return is_string($cachedData) ? $cachedData : null;
        } catch (\Throwable $th) {
            $this->logger->error('[MageStack][StatelessGuestValidator] Error while getting cache.', [
                'key' => $key,
                'message' => $th->getMessage(),
                'stack_trace' => $th->getTraceAsString(),
            ]);

            throw new RuntimeException("Unable to fetch data from cache");
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): void
    {
        try {
            $cacheKey = $this->generateCacheKey($key);
            $this->getCache()->remove($cacheKey);
        } catch (\Throwable $th) {
            $this->logger->error('[MageStack][StatelessGuestValidator] Error while deleting cache.', [
                'key' => $key,
                'message' => $th->getMessage(),
                'stack_trace' => $th->getTraceAsString(),
            ]);

            throw new RuntimeException("Unable to delete data from cache by key.");
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteByTags(array $tags): void
    {
        if (empty($tags)) {
            throw new RuntimeException("Tags can not be empty.");
        }
        try {
            $this->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, $tags);
        } catch (\Throwable $th) {
            $this->logger->error('[MageStack][StatelessGuestValidator] Error while deleting cache by tags.', [
                'message' => $th->getMessage(),
                'stack_trace' => $th->getTraceAsString(),
            ]);

            throw new RuntimeException("Unable to delete data from cache by tags.");
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteAll(): void
    {
        try {
            $this->getCache()->clean(Zend_Cache::CLEANING_MODE_ALL);
        } catch (\Throwable $th) {
            $this->logger->error('[MageStack][StatelessGuestValidator] Error while deleting all cache.', [
                'message' => $th->getMessage(),
                'stack_trace' => $th->getTraceAsString(),
            ]);

            throw new RuntimeException("Unable to fetch data from cache");
        }
    }

    /**
     * Get cache instance form runtime cache
     *
     * @return FrontendInterface
     *
     * @throws RuntimeException
     */
    private function getCache(): FrontendInterface
    {
        if (!$this->cache instanceof FrontendInterface) {
            $this->cache = $this->cacheFrontendPool->get(self::CACHE_IDENTIFIER);
        }

        if (!$this->cache instanceof FrontendInterface) {
            throw new RuntimeException("Can not instantiate cache object.");
        }

        return $this->cache;
    }

    /**
     * Generate cache key
     *
     * @param string $key
     * @return string
     */
    private function generateCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . $this->encryptor->getHash(
            $key,
            false
        );
    }
}
