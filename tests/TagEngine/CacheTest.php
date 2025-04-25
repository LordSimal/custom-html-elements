<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\TagEngine;

use FilesystemIterator;
use LordSimal\CustomHtmlElements\Error\ConfigException;
use LordSimal\CustomHtmlElements\TagEngine;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @see \LordSimal\CustomHtmlElements\TagEngine
 */
class CacheTest extends TestCase
{
    protected string $cacheDir = '';

    protected TagEngine $tagEngine;

    protected function setUp(): void
    {
        $this->cacheDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir);
        }
        $this->tagEngine = new TagEngine([
            'tag_directories' => [
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
            ],
            'enable_cache' => true,
            'cache_dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR,
        ]);
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $fileInfo) {
            $todo = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileInfo->getRealPath());
        }

        rmdir($this->cacheDir);
    }

    /**
     * Test a tag with a simple attribute (non-self-closing)
     *
     * @return void
     */
    public function testCacheWillBeFilledAndRead(): void
    {
        $element = '<c-youtube src="RLdsCL4RDf8"></c-youtube>';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
			<iframe width="560" height="315"  class=""
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
				
			</iframe>
HTML;
        $this->assertSame($expected, $result);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        $this->assertGreaterThan(0, iterator_count($files));

        $result = $this->tagEngine->parse($element);
        $this->assertSame($expected, $result);
    }

    public function testCacheWillThrowIfCacheDirIsEmpty(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Please set a `cache_dir` config');
        new TagEngine([
            'tag_directories' => [
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
            ],
            'enable_cache' => true,
        ]);
    }

    public function testCacheWillThrowIfCacheDirIsNotExistent(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Cache directory does not exist or is not writable');
        new TagEngine([
            'tag_directories' => [
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
            ],
            'enable_cache' => true,
            'cache_dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'non-existent',
        ]);
    }
}
