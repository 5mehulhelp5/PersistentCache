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

namespace MageStack\ParsistentCache\Test\Unit\Model;

use MageStack\ParsistentCache\Model\CacheRepository;
use Magento\Framework\App\Cache\Frontend\Pool as CacheFrontendPool;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * PHP unit test for cache repository
 * 
 * class CacheRepositoryTest
 * namespace MageStack\ParsistentCache\Test\Unit\Model
 */
class CacheRepositoryTest extends TestCase
{
    /**
     * @var CacheRepository
     */
    private CacheRepository $repository;

    /**
     * @var CacheFrontendPool
     */
    private CacheFrontendPool $cacheFrontendPoolMock;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptorMock;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $loggerMock;

    /**
     * @var FrontendInterface
     */
    private FrontendInterface $cacheMock;

    /**
     * Seup method for unit test
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->cacheFrontendPoolMock = $this->createMock(CacheFrontendPool::class);
        $this->encryptorMock = $this->createMock(EncryptorInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->cacheMock = $this->createMock(FrontendInterface::class);

        $this->encryptorMock
            ->method('getHash')
            ->willReturnCallback(fn($key) => hash('sha256', $key));

        $this->cacheFrontendPoolMock
            ->method('get')
            ->willReturn($this->cacheMock);

        $this->repository = new CacheRepository(
            $this->cacheFrontendPoolMock,
            $this->encryptorMock,
            $this->loggerMock
        );
    }

    /**
     * Test Save method happly flow
     * 
     * @return void
     */
    public function testSave(): void
    {
        $key = 'test_key';
        $data = 'some_data';
        $expectedKey = 'parsistent_' . hash('sha256', $key);

        $this->cacheMock
            ->expects($this->once())
            ->method('save')
            ->with($data, $expectedKey, ['parsistent'], 3600);

        $this->repository->save($key, $data);
    }

    /**
     * Test get method for happy flow
     * 
     * @return void
     */
    public function testGetReturnsData(): void
    {
        $key = 'test_key';
        $expectedKey = 'parsistent_' . hash('sha256', $key);
        $cachedValue = 'cached_data';

        $this->cacheMock
            ->expects($this->once())
            ->method('load')
            ->with($expectedKey)
            ->willReturn($cachedValue);

        $result = $this->repository->get($key);
        $this->assertEquals($cachedValue, $result);
    }

    /**
     * Test get method for null data return
     * 
     * @return void
     */
    public function testGetReturnsNullForInvalidData(): void
    {
        $key = 'test_key';
        $expectedKey = 'parsistent_' . hash('sha256', $key);

        $this->cacheMock
            ->expects($this->once())
            ->method('load')
            ->with($expectedKey)
            ->willReturn(false); // Non-string value

        $result = $this->repository->get($key);
        $this->assertNull($result);
    }

    /**
     * Test delete method for happy flow
     * 
     * @return void
     */
    public function testDelete(): void
    {
        $key = 'test_key';
        $expectedKey = 'parsistent_' . hash('sha256', $key);

        $this->cacheMock
            ->expects($this->once())
            ->method('remove')
            ->with($expectedKey);

        $this->repository->delete($key);
    }

    /**
     * Test delete by tags happy flow
     * 
     * @return void
     */
    public function testDeleteByTagsDeletesWithGivenTags(): void
    {
        $tags = ['custom_tag'];

        $this->cacheMock
            ->expects($this->once())
            ->method('clean')
            ->with(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, $tags);

        $this->repository->deleteByTags($tags);
    }

    /**
     * Test delete by tags method for empty arg
     * 
     * @return void
     */
    public function testDeleteByTagsIfTagsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tags can not be empty.');

        $this->repository->deleteByTags([]);
    }

    /**
     * Test delete by tag method for error handling
     * 
     * @return void
     */
    public function testDeleteByTagsLogsAndThrowsOnFailure(): void
    {
        $tags = ['some_tag'];

        $this->cacheMock
            ->expects($this->once())
            ->method('clean')
            ->willThrowException(new \Exception('Simulated failure'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Error while deleting cache by tags.'),
                $this->arrayHasKey('stack_trace')
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to delete data from cache by tags.');

        $this->repository->deleteByTags($tags);
    }

    /**
     * Test delete all method for happy flow
     * 
     * @return void
     */
    public function testDeleteAll(): void
    {
        $this->cacheMock
            ->expects($this->once())
            ->method('clean')
            ->with(\Zend_Cache::CLEANING_MODE_ALL);

        $this->repository->deleteAll();
    }

    /**
     * Test delete all method for error handling
     * 
     * @return void
     */
    public function testDeleteAllLogsAndThrowsOnFailure(): void
    {
        $this->cacheMock
            ->expects($this->once())
            ->method('clean')
            ->willThrowException(new \Exception('Fail all'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Error while deleting all cache.'),
                $this->arrayHasKey('stack_trace')
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to fetch data from cache');

        $this->repository->deleteAll();
    }

    /**
     * Test save method for error handling
     * 
     * @return void
     */
    public function testSaveThrowsExceptionOnFailure(): void
    {
        $key = 'bad_key';
        $data = 'data';
        $this->cacheMock
            ->method('save')
            ->willThrowException(new \Exception('Failed to save'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Error while saving cache.'),
                $this->arrayHasKey('stack_trace')
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to save data to persistance cache.');

        $this->repository->save($key, $data);
    }

    /**
     * Test get method for error handling 
     * 
     * @return void
     */
    public function testGetThrowsExceptionOnFailure(): void
    {
        $key = 'fail_key';
        $this->cacheMock
            ->method('load')
            ->willThrowException(new \Exception('Load failed'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to fetch data from cache');

        $this->repository->get($key);
    }

    /**
     * Test delete method for error handling
     * 
     * @return void
     */
    public function testDeleteThrowsExceptionOnFailure(): void
    {
        $key = 'fail_delete';
        $this->cacheMock
            ->method('remove')
            ->willThrowException(new \Exception('Remove failed'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to delete data from cache by key.');

        $this->repository->delete($key);
    }
}
