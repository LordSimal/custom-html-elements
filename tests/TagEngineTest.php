<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test;

use FilesystemIterator;
use LordSimal\CustomHtmlElements\TagEngine;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @see TagEngine
 */
class TagEngineTest extends TestCase
{
    protected const CACHE_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;

    protected function setUp(): void
    {
        if (!file_exists(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR);
        }
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::CACHE_DIR, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        rmdir(self::CACHE_DIR);
    }

    /**
     * Test a tab with a simple attribute
     *
     * @return void
     */
    public function testTagWithAttribute(): void
    {
        $element = '<c-youtube src="RLdsCL4RDf8"></c-youtube>';
        $tagEngine = new TagEngine([
            'tag_directories' => [__DIR__ . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR],
        ]);
        $result = $tagEngine->parse($element);
        $expected = <<<HTML
			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test self-closing tag variant
     *
     * @return void
     */
    public function testTagWithAttributeSelfClosing(): void
    {
        $element = '<c-youtube src="RLdsCL4RDf8" />';
        $tagEngine = new TagEngine([
            'tag_directories' => [__DIR__ . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR],
        ]);
        $result = $tagEngine->parse($element);
        $expected = <<<HTML
			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test multiple self-closing tag variants
     *
     * @return void
     */
    public function testMultipleTagsWithAttributeSelfClosing(): void
    {
        $element = '<c-youtube src="RLdsCL4RDf8" /><c-youtube src="RLdsCL4RDf8" />';
        $tagEngine = new TagEngine([
            'tag_directories' => [__DIR__ . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR],
        ]);
        $result = $tagEngine->parse($element);
        $expected = <<<HTML
			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe>			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test tags work with multiple attributes
     *
     * @return void
     */
    public function testTagWithMultipleAttributes(): void
    {
        $element = '<c-button type="primary" text="Click me" url="/something/stupid" />';
        $tagEngine = new TagEngine([
            'tag_directories' => [__DIR__ . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR],
        ]);
        $result = $tagEngine->parse($element);
        $expected = <<<HTML
			<a href="/something/stupid" class="c-button c-button--primary">Click me</a>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test tags work in other custom directories
     *
     * @return void
     */
    public function testTagInSubFolder(): void
    {
        $element = '<c-github></c-github>';
        $tagEngine = new TagEngine([
            'tag_directories' => [
                __DIR__ . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
                __DIR__ . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
            ],
        ]);
        $result = $tagEngine->parse($element);
        $expected = <<<HTML
			This is a render from a plugin tag
            
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test inner content is passed down to the tag and can be outputted
     *
     * @return void
     */
    public function testTagWithInnerContent(): void
    {
        $element = '<c-github>Inner Content</c-github>';
        $tagEngine = new TagEngine([
            'tag_directories' => [
                __DIR__ . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
                __DIR__ . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
            ],
        ]);
        $result = $tagEngine->parse($element);
        $expected = <<<HTML
			This is a render from a plugin tag
            Inner Content
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Make sure nested tags don't get rendered by default
     *
     * @return void
     */
    public function testNestedContentDoesntRenderByDefault(): void
    {
        $element = '<c-nested />';
        $tagEngine = new TagEngine([
            'tag_directories' => [
                __DIR__ . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
                __DIR__ . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
            ],
        ]);
        $result = $tagEngine->parse($element);
        $expected = <<<HTML
			<c-github></c-github>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test that sub-tags will also be rendered if the config
     *
     * @return void
     */
    public function testNestedContentRendersWithConfig(): void
    {
        $element = '<c-nested />';
        $tagEngine = new TagEngine([
            'tag_directories' => [
                __DIR__ . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
                __DIR__ . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
            ],
            'sniff_for_nested_tags' => true,
        ]);
        $result = $tagEngine->parse($element);
        $expected = <<<HTML
			This is a render from a plugin tag
            
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test that sub-tags will trigger cache correctly
     *
     * @return void
     */
    public function testNestedContentRendersWithCache(): void
    {
        $element = '<c-nested />';
        $tagEngine = new TagEngine([
            'tag_directories' => [
                __DIR__ . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
                __DIR__ . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
            ],
            'sniff_for_nested_tags' => true,
            'cache_tags' => true,
            'cache_directory' => self::CACHE_DIR,
        ]);
        $result = $tagEngine->parse($element);
        $expected = <<<HTML
			This is a render from a plugin tag
            
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test tag variant and normal HTML
     *
     * @return void
     */
    public function testTagWithAttributeAndNormalHTML(): void
    {
        $element = '<c-youtube src="RLdsCL4RDf8"></c-youtube><div>Test</div>';
        $tagEngine = new TagEngine([
            'tag_directories' => [__DIR__ . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR],
        ]);
        $result = $tagEngine->parse($element);
        $expected = <<<HTML
			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe><div>Test</div>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test self-closing tag variant and normal HTML
     *
     * @return void
     */
    public function testTagWithAttributeSelfClosingAndNormalHTML(): void
    {
        $element = '<c-youtube src="RLdsCL4RDf8" /><div>Test</div>';
        $tagEngine = new TagEngine([
            'tag_directories' => [__DIR__ . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR],
        ]);
        $result = $tagEngine->parse($element);
        $expected = <<<HTML
			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe><div>Test</div>
HTML;
        $this->assertSame($expected, $result);
    }
}
