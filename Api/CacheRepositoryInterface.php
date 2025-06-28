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
 * @package   MageStack_PersistentCache
 * @author    Amit Biswas <amit.biswas.webdeveloper@gmail.com>
 * @copyright 2025 MageStack
 * @license   https://opensource.org/licenses/MIT  MIT License
 * @link      https://github.com/attherateof/PersistentCache
 */

declare(strict_types=1);

namespace MageStack\PersistentCache\Api;

use RuntimeException;

/**
 * Persistance cache repository interface
 *
 * interface CacheRepositoryInterface
 * namespace MageStack\PersistentCache\Api
 *
 * @api
 *
 */
interface CacheRepositoryInterface
{
    /**
     * Save data to cache
     *
     * @param string $key
     * @param string $serializedData
     * @param array $tags
     * @phpstan-param array<string, mixed> $tags
     * @param int $lifetime
     * @return void
     *
     * @throws RuntimeException
     */
    public function save(
        string $key,
        string $serializedData,
        array $tags = [],
        ?int $lifetime = null
    ): void;

    /**
     * Get data from cache
     *
     * @param string $key
     *
     * @return null|string
     *
     * @throws RuntimeException
     */
    public function get(string $key): ?string;

    /**
     * Delete data from cache
     *
     * @param string $key
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public function delete(string $key): void;

    /**
     * Delete data from cache by tags
     *
     * @param string[] $tags
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public function deleteByTags(array $tags): void;

    /**
     * Delete all cache data
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public function deleteAll(): void;
}
